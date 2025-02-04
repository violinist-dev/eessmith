<?php

namespace Drupal\upgrade_status;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Extension\Extension;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Template\TwigEnvironment;
use DrupalFinder\DrupalFinder;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Twig\Util\DeprecationCollector;
use Twig\Util\TemplateDirIterator;

final class DeprecationAnalyzer {

  /**
   * Upgrade status scan result storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $scanResultStorage;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Path to the PHPStan neon configuration.
   *
   * @var string
   */
  protected $phpstanNeonPath;

  /**
   * Path to the vendor directory.
   *
   * @var string
   */
  protected $vendorPath;

  /**
   * Path to the binaries.
   *
   * @var string
   */
  protected $binPath;

  /**
   * Temporary directory to use for running phpstan.
   *
   * @var string
   */
  protected $temporaryDirectory;

  /**
   * HTTP Client for drupal.org API calls.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The Twig environment.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twigEnvironment;

  /**
   * The library deprecation analyzer.
   *
   * @var \Drupal\upgrade_status\LibraryDeprecationAnalyzer
   */
  protected $libraryDeprecationAnalyzer;

  /**
   * The theme function deprecation analyzer.
   *
   * @var \Drupal\upgrade_status\ThemeFunctionDeprecationAnalyzer
   */
  protected $themeFunctionDeprecationAnalyzer;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Drupal project finder.
   *
   * @var \DrupalFinder\DrupalFinder
   */
  protected $finder;

  /**
   * Whether the analyzer environment is initialized.
   *
   * @var bool
   */
  protected $environmentInitialized = FALSE;

  /**
   * Constructs a deprecation analyzer.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key/value factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \GuzzleHttp\Client $http_client
   *   HTTP client.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Template\TwigEnvironment $twig_environment
   *   The Twig environment.
   * @param \Drupal\upgrade_status\LibraryDeprecationAnalyzer $library_deprecation_analyzer
   *   The library deprecation analyzer.
   * @param \Drupal\upgrade_status\ThemeFunctionDeprecationAnalyzer $theme_function_deprecation_analyzer
   *   The theme function deprecation analyzer.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    KeyValueFactoryInterface $key_value_factory,
    LoggerInterface $logger,
    Client $http_client,
    FileSystemInterface $file_system,
    TwigEnvironment $twig_environment,
    LibraryDeprecationAnalyzer $library_deprecation_analyzer,
    ThemeFunctionDeprecationAnalyzer $theme_function_deprecation_analyzer,
    TimeInterface $time
  ) {
    $this->scanResultStorage = $key_value_factory->get('upgrade_status_scan_results');
    $this->logger = $logger;
    $this->httpClient = $http_client;
    $this->fileSystem = $file_system;
    $this->twigEnvironment = $twig_environment;
    $this->libraryDeprecationAnalyzer = $library_deprecation_analyzer;
    $this->themeFunctionDeprecationAnalyzer = $theme_function_deprecation_analyzer;
    $this->time = $time;
  }

  /**
   * Initialize the external environment.
   *
   * @throws \Exception
   *   In case initialization failed. The analyzer will not work in this case.
   */
  public function initEnvironment() {
    if (!empty($this->environmentInitialized)) {
      // Already successfully initialized, no need to do it again.
      return;
    }

    $this->finder = new DrupalFinder();
    $this->finder->locateRoot(DRUPAL_ROOT);

    $this->vendorPath = $this->finder->getVendorDir();
    $this->binPath = $this->findBinPath();

    if (function_exists('file_directory_temp')) {
      // This is fallback code for 8.7.x and below. It's not called on later
      // versions, so we don't nee to "fix" it.
      // @noRector
      // @phpstan-ignore-next-line
      $system_temporary = file_directory_temp();
    }
    else {
      $system_temporary = $this->fileSystem->getTempDirectory();
    }
    $this->temporaryDirectory = $system_temporary . '/upgrade_status';
    if (!file_exists($this->temporaryDirectory)) {
      $this->prepareTempDirectory();
    }

    $this->phpstanNeonPath = $this->temporaryDirectory . '/deprecation_testing.neon';
    $this->createModifiedNeonFile();

    $this->environmentInitialized = TRUE;
  }

  /**
   * Finds bin-dir location.
   *
   * This can be set in composer.json via `bin-dir` config and may not be
   * inside the vendor directory. The logic somewhat duplicates
   * DrupalFinder's vendor directory detection for best developer guidance
   * in case of errors.
   *
   * @return string
   *   Bin directory path if found.
   *
   * @throws \Exception
   */
  protected function findBinPath() {
    $composer_name = trim(getenv('COMPOSER')) ?: 'composer.json';
    $composer_json_path = $this->finder->getComposerRoot() . '/' . $composer_name;
    if ($composer_json_path && file_exists($composer_json_path)) {
      $json = json_decode(file_get_contents($composer_json_path), TRUE);
      if (is_null($json) || !is_array($json)) {
        throw new \Exception('Unable to decode composer information from ' . $composer_json_path . '.');
      }
    }
    else {
      throw new \Exception('The composer.json file was not found at ' . $composer_json_path . '.');
    }

    // If a bin-dir is specified, that is most specific.
    if (isset($json['config']['bin-dir'])) {
      $binPath = $this->finder->getComposerRoot() . '/' . $json['config']['bin-dir'];
      if (file_exists($binPath . '/phpstan')) {
        return $binPath;
      }
      else {
        throw new \Exception('The PHPStan binary was not found in the bin-dir specified by ' . $composer_json_path . '. Attempted: ' . $binPath . '/phpstan.');
      }
    }

    // If a vendor-dir is specified, that is slightly less specific.
    if (isset($json['config']['vendor-dir'])) {
      $binPath = $this->finder->getComposerRoot() . '/' . $json['config']['vendor-dir'] . '/bin';
      if (file_exists($binPath . '/phpstan')) {
        return $binPath;
      }
      else {
        throw new \Exception('The PHPStan binary was not found in the vendor-dir specified by ' . $composer_json_path . '. Attempted: ' . $binPath . '/phpstan.');
      }
    }

    // Try the assumed default vendor directory as a last resort.
    $binPath = $this->finder->getComposerRoot() . '/vendor/bin';
    if (file_exists($binPath . '/phpstan')) {
      return $binPath;
    }

    throw new \Exception('The PHPStan binary was not found in the default vendor directory based on the location of ' . $composer_json_path . '. You may need to configure a vendor-dir in composer.json. See https://getcomposer.org/doc/06-config.md#vendor-dir. Attempted: ' . $binPath . '/phpstan.');
  }

  /**
   * Analyze the codebase of an extension including all its sub-components.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to analyze.
   *
   * @return null
   *   Errors are logged to the logger, data is stored to keyvalue storage.
   */
  public function analyze(Extension $extension) {
    try {
      $this->initEnvironment();
    }
    catch (\Exception $e) {
      // Should not get here as integrations are expected to invoke
      // initEnvironment() first by itself to ensure the environment
      // is going to work when needed (and inform users about any
      // issues). That said, if they did not do that and there was
      // no issue with the environment, then they are lucky.
      return;
    }

    $project_dir = DRUPAL_ROOT . '/' . $extension->getPath();
    $this->logger->notice('Processing %path.', ['%path' => $project_dir]);

    $output = [];
    $error_filename = $this->temporaryDirectory . '/phpstan_error_output';
    $command = $this->binPath . '/phpstan analyse --error-format=json -c ' . $this->phpstanNeonPath . ' ' . $project_dir . ' 2> ' . $error_filename;
    exec($command, $output);

    $json = json_decode(implode('', $output), TRUE);
    if (!isset($json['files']) || !is_array($json['files'])) {
       $stdout = trim(implode('', $output)) ?: 'Empty.';
       $stderr = trim(file_get_contents($error_filename)) ?: 'Empty.';
       $formatted_error =
         "<h6>PHPStan command failed:</h6> <p>" . $command .
         "</p> <h6>Command output:</h6> <p>" . $stdout .
         "</p> <h6>Command error:</h6> <p>" . $stderr . '</p>';
       $this->logger->error('%phpstan_fail', ['%phpstan_fail' => strip_tags($formatted_error)]);
       $json = [
         'files' => [
           // Add a failure message with the nonexistent 'PHPStan failed'
           // filename, so the error conforms to the expected format.
           'PHPStan failed' => [
             'messages' => [
               [
                 'message' => $formatted_error,
                 'line' => 0,
               ],
             ],
           ]
         ],
         'totals' => [
           'errors' => 1,
           'file_errors' => 1,
         ],
       ];
    }
    $result = [
      'date' => $this->time->getRequestTime(),
      'data' => $json,
    ];

    $twig_deprecations = $this->analyzeTwigTemplates($extension->getPath());
    foreach ($twig_deprecations as $twig_deprecation) {
      preg_match('/\s([a-zA-Z0-9\_\-\/]+.html\.twig)\s/', $twig_deprecation, $file_matches);
      preg_match('/\s(\d+).?$/', $twig_deprecation, $line_matches);
      $twig_deprecation = preg_replace('! in (.+)\.twig at line \d+\.!', '.', $twig_deprecation);
      $twig_deprecation .= ' See https://drupal.org/node/3071078.';
      $result['data']['files'][$file_matches[1]]['messages'][] = [
        'message' => $twig_deprecation,
        'line' => $line_matches[1] ?: 0,
      ];
      $result['data']['totals']['errors']++;
      $result['data']['totals']['file_errors']++;
    }

    $deprecation_messages = $this->libraryDeprecationAnalyzer->analyze($extension);
    foreach ($deprecation_messages as $deprecation_message) {
      $result['data']['files'][$deprecation_message->getFile()]['messages'][] = [
        'message' => $deprecation_message->getMessage(),
        'line' => $deprecation_message->getLine(),
      ];
      $result['data']['totals']['errors']++;
      $result['data']['totals']['file_errors']++;
    }

    $theme_function_deprecations = $this->themeFunctionDeprecationAnalyzer->analyze($extension);
    foreach ($theme_function_deprecations as $deprecation_message) {
      $result['data']['files'][$deprecation_message->getFile()]['messages'][] = [
        'message' => $deprecation_message->getMessage(),
        'line' => $deprecation_message->getLine(),
      ];
      $result['data']['totals']['errors']++;
      $result['data']['totals']['file_errors']++;
    }

    // Assume this project is ready for the next major core version unless proven otherwise.
    $result['data']['totals']['upgrade_status_split']['declared_ready'] = TRUE;

    $info_files = $this->getSubExtensionInfoFiles($project_dir);
    foreach ($info_files as $info_file) {
      try {

        // Manually add on info file incompatibility to results. Reading
        // .info.yml files directly, not from extension discovery because that
        // is cached.
        $info = Yaml::decode(file_get_contents($info_file)) ?: [];
        if (!empty($info['package']) && $info['package'] == 'Testing' && !strpos($info_file, '/upgrade_status_test')) {
          // If this info file was for a testing project other than our own
          // testing projects, ignore it.
          continue;
        }
        $error_path = str_replace(DRUPAL_ROOT . '/', '', $info_file);

        // Check for missing base theme key.
        if ($info['type'] === 'theme') {
          if (!isset($info['base theme'])) {
            $result['data']['files'][$error_path]['messages'][] = [
              'message' => "The now required 'base theme' key is missing. See https://www.drupal.org/node/3066038.",
              'line' => 0,
            ];
            $result['data']['totals']['errors']++;
            $result['data']['totals']['file_errors']++;
          }
        }

        if (!isset($info['core_version_requirement'])) {
          $result['data']['files'][$error_path]['messages'][] = [
            'message' => "Add core_version_requirement: ^8 || ^9 to designate that the module is compatible with Drupal 9. See https://drupal.org/node/3070687.",
            'line' => 0,
          ];
          $result['data']['totals']['errors']++;
          $result['data']['totals']['file_errors']++;
          $result['data']['totals']['upgrade_status_split']['declared_ready'] = FALSE;
        }
        elseif (!ProjectCollector::isCompatibleWithNextMajorDrupal($info['core_version_requirement'])) {
          $result['data']['files'][$error_path]['messages'][] = [
            'message' => "Value of core_version_requirement: {$info['core_version_requirement']} is not compatible with the next major version of Drupal core. See https://drupal.org/node/3070687.",
            'line' => 0,
          ];
          $result['data']['totals']['errors']++;
          $result['data']['totals']['file_errors']++;
          $result['data']['totals']['upgrade_status_split']['declared_ready'] = FALSE;
        }

        // @todo
        //   Change values to ExtensionLifecycle class constants once at least
        //   Drupal 9.3 is required.
        if (!empty($info['lifecycle'])) {
          $link = !empty($info['lifecycle_link']) ? $info['lifecycle_link'] : 'https://www.drupal.org/node/3215042';
          if ($info['lifecycle'] == 'deprecated') {
            $result['data']['files'][$error_path]['messages'][] = [
              'message' => "This extension is deprecated. Don't use it. See $link.",
              'line' => 0,
            ];
            $result['data']['totals']['errors']++;
            $result['data']['totals']['file_errors']++;
            $result['data']['totals']['upgrade_status_split']['declared_ready'] = FALSE;
          }
          elseif ($info['lifecycle'] == 'obsolete') {
            $result['data']['files'][$error_path]['messages'][] = [
              'message' => "This extension is obsolete. Obsolete extensions are usually uninstalled automatically when not needed anymore. You only need to do something about this if the uninstallation was unsuccesful. See $link.",
              'line' => 0,
            ];
            $result['data']['totals']['errors']++;
            $result['data']['totals']['file_errors']++;
            $result['data']['totals']['upgrade_status_split']['declared_ready'] = FALSE;
          }
        }

      } catch (InvalidDataTypeException $e) {
        $result['data']['files'][$error_path]['messages'][] = [
          'message' => 'Parse error. ' . $e->getMessage(),
          'line' => 0,
        ];
        $result['data']['totals']['errors']++;
        $result['data']['totals']['file_errors']++;
        $result['data']['totals']['upgrade_status_split']['declared_ready'] = FALSE;
      }

      // No need to check info files for PHP 8 compatibility information because
      // they can only define minimal PHP versions not maximum or excluded PHP
      // versions.
    }

    // Manually add on composer.json file incompatibility to results.
    if (file_exists($project_dir . '/composer.json')) {
      $composer_json = json_decode(file_get_contents($project_dir . '/composer.json'));
      if (empty($composer_json) || !is_object($composer_json)) {
        $result['data']['files'][$extension->getPath() . '/composer.json']['messages'][] = [
          'message' => "Parse error in composer.json. Having a composer.json is not a requirement in general, but if there is one, it should be valid. See https://drupal.org/node/2514612.",
          'line' => 0,
        ];
        $result['data']['totals']['errors']++;
        $result['data']['totals']['file_errors']++;
        $result['data']['totals']['upgrade_status_split']['declared_ready'] = FALSE;
      }
      elseif (!empty($composer_json->require->{'drupal/core'}) && !projectCollector::isCompatibleWithNextMajorDrupal($composer_json->require->{'drupal/core'})) {
        $result['data']['files'][$extension->getPath() . '/composer.json']['messages'][] = [
          'message' => "The drupal/core requirement is not compatible with the next major version of Drupal. Either remove it or update it to be compatible. See https://drupal.org/node/2514612#s-drupal-9-compatibility.",
          'line' => 0,
        ];
        $result['data']['totals']['errors']++;
        $result['data']['totals']['file_errors']++;
        $result['data']['totals']['upgrade_status_split']['declared_ready'] = FALSE;
      }
      elseif ((projectCollector::getDrupalCoreMajorVersion() > 8) && !empty($composer_json->require->{'php'} && !projectCollector::isCompatibleWithPHP8($composer_json->require->{'php'}))) {
        $result['data']['files'][$extension->getPath() . '/composer.json']['messages'][] = [
          'message' => "The PHP requirement is not compatible with PHP 8. Once the codebase is actually compatible, either remove this limitation or update it to be compatible.",
          'line' => 0,
        ];
        $result['data']['totals']['errors']++;
        $result['data']['totals']['file_errors']++;
        $result['data']['totals']['upgrade_status_split']['declared_ready'] = FALSE;
      }
    }

    // Assume next step is to relax (there were no errors found).
    $result['data']['totals']['upgrade_status_next'] = ProjectCollector::NEXT_RELAX;

    foreach ($result['data']['files'] as $path => &$errors) {
      foreach ($errors['messages'] as &$error) {

        // Overwrite message with processed text. Save category.
        [$message, $category] = $this->categorizeMessage($error['message'], $extension);
        $error['message'] = $message;
        $error['upgrade_status_category'] = $category;

        // If the category was 'rector' that means at least one error was
        // identified as covered by rector, so next step should be to run
        // rector on this project.
        if ($category == 'rector') {
          $result['data']['totals']['upgrade_status_next'] = ProjectCollector::NEXT_RECTOR;
        }
        // If the category was not rector, if the next step is still to
        // relax, modify that to fix manually.
        elseif ($result['data']['totals']['upgrade_status_next'] == ProjectCollector::NEXT_RELAX) {
          $result['data']['totals']['upgrade_status_next'] = ProjectCollector::NEXT_MANUAL;
        }

        // Sum up the error based on the category it ended up in. Split the
        // categories into two high level buckets needing attention now or
        // later for compatibility with the next major version. Issues in the
        // 'ignore' category are intentionally not counted in either.
        @$result['data']['totals']['upgrade_status_category'][$category]++;
        if (in_array($category, ['safe', 'old', 'rector'])) {
          @$result['data']['totals']['upgrade_status_split']['error']++;
        }
        elseif (in_array($category, ['later', 'uncategorized'])) {
          @$result['data']['totals']['upgrade_status_split']['warning']++;
        }
      }
    }

    // For contributed projects, attempt to grab upgrade plan information.
    if (!empty($extension->info['project'])) {
      try {
        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->httpClient->request('GET', 'https://www.drupal.org/api-d7/node.json?field_project_machine_name=' . $extension->getName());
        if ($response->getStatusCode()) {
          $data = json_decode($response->getBody(), TRUE);
          if (!empty($data['list'][0]['field_next_major_version_info']['value'])) {
            $result['plans'] = str_replace('href="/', 'href="https://drupal.org/', $data['list'][0]['field_next_major_version_info']['value']);
            // @todo implement "replaced by" collection once drupal.org exposes
            // that in an accessible way
            // @todo once/if drupal.org deprecation testing is in place, grab
            // the status from there so we know if it improves by updating
          }
        }
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }

    // Store the analysis results in our storage bin.
    $this->scanResultStorage->set($extension->getName(), $result);
  }

  /**
   * Analyzes twig templates for calls of deprecated code.
   *
   * @param $directory
   *   The directory which Twig templates should be analyzed.
   *
   * @return array
   */
  protected function analyzeTwigTemplates($directory) {
    $iterator = new TemplateDirIterator(
      new TwigRecursiveIterator($directory)
    );
    return (new DeprecationCollector($this->twigEnvironment))
      ->collect($iterator);
  }

  /**
   * Prepare temporary directories for Upgrade Status.
   *
   * The created directories in Drupal's temporary directory are needed to
   * dynamically set a temporary directory for PHPStan's cache in the neon file
   * provided by Upgrade Status.
   *
   * @throws \Exception
   *   If creating the temporary directory failed.
   */
  protected function prepareTempDirectory() {
    $success = $this->fileSystem->prepareDirectory($this->temporaryDirectory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    if (!$success) {
      throw new \Exception('Unable to create temporary directory for Upgrade Status at ' . $this->temporaryDirectory);
    }

    $phpstan_cache_directory = $this->temporaryDirectory . '/phpstan';
    $success = $this->fileSystem->prepareDirectory($phpstan_cache_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    if (!$success) {
      throw new \Exception('Unable to create temporary directory for PHPStan at ' . $phpstan_cache_directory);
    }
  }

  /**
   * Creates the final config file in the temporary directory.
   *
   * @throws \Exception
   *   If the PHPStan configuration file cannot be written.
   */
  protected function createModifiedNeonFile() {
    $module_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'upgrade_status');
    $config = file_get_contents($module_path . '/deprecation_testing_template.neon');
    $config = str_replace(
      'parameters:',
      "parameters:\n\ttmpDir: '" . $this->temporaryDirectory . '/phpstan' . "'\n" .
        "\tdrupal:\n\t\tdrupal_root: '" . DRUPAL_ROOT . "'",
      $config
    );

    if (!class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
      $extension_neon = $this->vendorPath . '/mglaman/phpstan-drupal/extension.neon';
      $rules_neon = $this->vendorPath . '/phpstan/phpstan-deprecation-rules/rules.neon';
      if (!file_exists($extension_neon) || !file_exists($rules_neon)) {
        throw new \Exception('Vendor source files were not found. You may need to configure a vendor-dir in composer.json. See https://getcomposer.org/doc/06-config.md#vendor-dir. Missing ' . $extension_neon . ' and ' . $rules_neon . '.');
      }
      $config .= "\nincludes:\n\t- '" . $extension_neon . "'\n\t- '" . $rules_neon . "'\n";
    }

    $success = file_put_contents($this->phpstanNeonPath, $config);
    if (!$success) {
      throw new \Exception('Unable to write configuration for PHPStan to ' . $this->phpstanNeonPath . '.');
    }
  }

  /**
   * Annotate and categorize the error message.
   *
   * @param string $error
   *   Error message as identified by phpstan.
   * @param \Drupal\Core\Extension\Extension $extension
   *   Extension where the error was found.
   *
   * @return array
   *   Two item array. The reformatted error and the category.
   */
  protected function categorizeMessage(string $error, Extension $extension) {
    // Make the error more readable in case it has the deprecation text.
    $error = preg_replace('!\s+!', ' ', $error);
    $error = preg_replace('!:\s+(in|as of)!', '. Deprecated \1', $error);
    $error = preg_replace('!(u|U)se \\\\Drupal!', '\1se Drupal', $error);

    // TestBase and WebTestBase replacements are available at least from Drupal
    // 8.6.0, so use that version number. Otherwise use the number from the
    // message.
    $version = '';
    if (preg_match('!\\\\(Web|)TestBase. Deprecated in [Dd]rupal[ :]8\.8\.0 !', $error)) {
      $version = '8.6.0';
      $error .= " Replacement available from drupal:8.6.0.";
    }
    elseif (preg_match('!Deprecated (in|as of) [Dd]rupal[ :](\d+\.\d)!', $error, $version_found)) {
      $version = $version_found[2];
    }

    // Set a default category for the messages we can't categorize.
    $category = 'uncategorized';

    if (!empty($version)) {

      // Categorize deprecations for contributed projects based on
      // community rules.
      if (!empty($extension->info['project'])) {
        // If the found deprecation is older or equal to the oldest
        // supported core version, it should be old enough to update
        // either way.
        if (version_compare($version, ProjectCollector::getOldestSupportedMinor()) <= 0) {
          $category = 'old';
        }
        // If the deprecation is not old and we are dealing with a contrib
        // module, the deprecation should be dealt with later.
        else {
          $category = 'later';
        }
      }
      // For custom projects, look at this site's version specifically.
      else {
        // If the found deprecation is older or equal to the current
        // Drupal version on this site, it should be safe to update.
        if (version_compare($version, \Drupal::VERSION) <= 0) {
          $category = 'safe';
        }
        else {
          $category = 'later';
        }
      }
    }

    // If the error is covered by rector, override the result.
    if ($this->isRectorCovered($error)) {
      $category = 'rector';
    }

    // If the deprecation is already for after the next Drupal major, put it in the
    // ignore category. This overwrites any categorization before intentionally.
    if (preg_match('!(will be|is) removed (before|from) [Dd]rupal[ :](\d+)\.!', $error, $version_removed)) {
      if ($version_removed[3] > ProjectCollector::getDrupalCoreMajorVersion() + 1) {
        $category = 'ignore';
      }
    }

    return [$error, $category];
  }

  /**
   * Checks whether an error message is covered by rector.
   *
   * @return bool
   */
  protected function isRectorCovered($string) {
    // Hardcoded lo-fi implementation for now. This should be the same as in
    // https://git.drupalcode.org/project/deprecation_status/-/blob/script/stats.php
    $rector_covered = [
      // 0.3.3
      'Call to deprecated function drupal_set_message(). Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use Drupal\Core\Messenger\MessengerInterface::addMessage() instead.',
      'Call to deprecated method entityManager() of class Drupal. Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal::entityTypeManager() instead in most cases. If the needed method is not on \Drupal\Core\Entity\EntityTypeManagerInterface, see the deprecated \Drupal\Core\Entity\EntityManager to find the correct interface or service.',
      'Call to deprecated method entityManager() of class Drupal\Core\Controller\ControllerBase. Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Most of the time static::entityTypeManager() is supposed to be used instead.',
      'Call to deprecated function db_insert(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call insert() on it. For example,',
      'Call to deprecated function db_select(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call select() on it. For example,',
      'Call to deprecated function db_query(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call query() on it. For example,',
      'Call to deprecated function file_prepare_directory(). Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use Drupal\Core\File\FileSystemInterface::prepareDirectory().',
      'Call to deprecated method getMock() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead.',
      'Call to deprecated method getMock() of class Drupal\KernelTests\KernelTestBase. Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead.',
      'Call to deprecated method getMock() of class Drupal\Tests\UnitTestCase. Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead.',
      'Call to deprecated method url() of class Drupal. Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Instead create a \Drupal\Core\Url object directly, for example using Url::fromRoute().',

      // 0.4.0
      'Call to deprecated function format_date(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal::service(\'date.formatter\')->format().',
      'Call to deprecated method strtolower() of class Drupal\Component\Utility\Unicode. Deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use mb_strtolower() instead.',
      'Call to deprecated constant FILE_CREATE_DIRECTORY: Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY.',
      'Call to deprecated constant FILE_EXISTS_REPLACE: Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE.',
      'Call to deprecated method l() of class Drupal. Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Core\Link::fromTextAndUrl() instead.',
      'Call to deprecated function drupal_render(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use the',
      'Call to deprecated function drupal_render_root(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Core\Render\RendererInterface::renderRoot() instead.',

      // 0.5.0
      'Call to deprecated function file_unmanaged_save_data(). Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use Drupal\Core\File\FileSystemInterface::saveData().',

      // 0.5.1
      'Call to deprecated constant FILE_MODIFY_PERMISSIONS: Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS.',
      'Call to deprecated function db_delete(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call delete() on it. For example,',

      // 0.5.2
      'Call to deprecated function entity_get_form_display(). Deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use EntityDisplayRepositoryInterface::getFormDisplay() instead.',
      'Call to deprecated function entity_get_display(). Deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use EntityDisplayRepositoryInterface::getViewDisplay() instead.',
      'Call to deprecated constant REQUEST_TIME. Deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use Drupal::time()->getRequestTime().',
      'Call to deprecated method urlInfo() of class Drupal\Core\Entity\EntityInterface. Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Core\Entity\EntityInterface::toUrl() instead.',
      'Call to deprecated function file_scan_directory(). Deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use Drupal\Core\File\FileSystemInterface::scanDirectory() instead.',
      'Call to deprecated function file_default_scheme(). Deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use Drupal::config(\'system.file\')->get(\'default_scheme\') instead.',
      'Call to deprecated function db_update(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call update() on it. For example,',

      // 0.5.3
      'Call to deprecated method strtolower() of class Drupal\Component\Utility\Unicode. Deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use mb_strtolower() instead.',
      'Call to deprecated method strlen() of class Drupal\Component\Utility\Unicode. Deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use mb_strlen() instead.',
      'Call to deprecated method link() of class Drupal\Core\Entity\EntityInterface. Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Core\EntityInterface::toLink()->toString() instead.',
      'Call to deprecated function entity_load(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use the entity type storage\'s load() method.',
      'Call to deprecated function node_load(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\node\Entity\Node::load().',
      'Call to deprecated function file_load(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\file\Entity\File::load().',
      'Call to deprecated function user_load(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\user\Entity\User::load().',
      'Call to deprecated function file_directory_temp(). Deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use Drupal\Core\File\FileSystemInterface::getTempDirectory() instead.',
      'Call to deprecated function file_directory_os_temp(). Deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use Drupal\Component\FileSystem\FileSystem::getOsTemporaryDirectory().',
      'Call to deprecated function drupal_realpath(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Core\File\FileSystem::realpath().',
      'Call to deprecated function file_uri_target(). Deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface::getTarget() instead.',

      // 0.5.4
      'Call to deprecated method format() of class Drupal\Component\Utility\SafeMarkup. Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Component\Render\FormattableMarkup.',
      'Call to deprecated constant FILE_EXISTS_RENAME: Deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use Drupal\Core\File\FileSystemInterface::EXISTS_RENAME.',
      // Covered below with the pattern.
      //'Call to deprecated method l() of class [redacted]. Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use Drupal\Core\Link::fromTextAndUrl() instead.',
      'Call to deprecated function entity_create(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use The method overriding Entity::create() for the entity type, e.g. \Drupal\node\Entity\Node::create() if the entity type is known. If the entity type is variable, use the entity storage\'s create() method to construct a new entity:',

      // 0.5.5
      // No new rules

      // 0.5.6
      'Call to deprecated constant DATETIME_STORAGE_TIMEZONE: Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::STORAGE_TIMEZONE instead.',
      'Call to deprecated constant DATETIME_DATETIME_STORAGE_FORMAT: Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT instead.',
      'Call to deprecated constant DATETIME_DATE_STORAGE_FORMAT: Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATE_STORAGE_FORMAT instead.',

      // 0.10.0
      'Call to deprecated method getLowercaseLabel() of class Drupal\Core\Entity\EntityTypeInterface. Deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, you should call getSingularLabel(). See https://www.drupal.org/node/3075567',
      'Call to deprecated function entity_delete_multiple(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use the entity storage\'s \Drupal\Core\Entity\EntityStorageInterface::delete() method to delete multiple entities:',
      'Call to deprecated function entity_view(). Deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use the entity view builder\'s view() method for creating a render array:',

      // 0.11.0
      // No new rules

      // 0.11.1
      'Call to deprecated method drupalPostForm() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use $this->submitForm() instead.',

      // yet unreleased
      'Call to deprecated method assertText() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use - $this->assertSession()->responseContains() for non-HTML responses, like XML or Json. - $this->assertSession()->pageTextContains() for HTML responses. Unlike the deprecated assertText(), the passed text should be HTML decoded, exactly as a human sees it in the browser.',
      'Call to deprecated method assertEqual() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertEquals() instead.',
      'Call to deprecated method assertIdentical() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertSame() instead.',
      'Call to deprecated method assertResponse() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->statusCodeEquals() instead.',
      'Call to deprecated method assertRaw() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseContains() instead.',
      'Call to deprecated method assertFieldByName() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldExists() or $this->assertSession()->buttonExists() or $this->assertSession()->fieldValueEquals() instead.',
      'Call to deprecated method buildXPathQuery() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->buildXPathQuery() instead.',
      'Call to deprecated method assertHeader() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderEquals() instead.',
      'Call to deprecated method assertNoCacheTag() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderNotContains() instead.',
      'Call to deprecated method assertCacheTag() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderContains() instead.',
      'Call to deprecated method assertNoPattern() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseNotMatches() instead.',
      'Call to deprecated method assertPattern() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseMatches() instead.',
      'Call to deprecated method assertEscaped() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->assertEscaped() instead.',
      // assertNoEscaped() rule exists but no instance in contrib.
      'Call to deprecated method assertNotEqual() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertNotEquals() instead.',
      'Call to deprecated method assertNotIdentical() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertNotSame() instead.',
      // assertIdenticalObject() rule exists but no instance in contrib.
      'Call to deprecated method assert() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.0.0 and is removed from drupal:10.0.0. Use $this->assertTrue() instead.',
      'Call to deprecated method assertElementNotPresent() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->elementNotExists() instead.',
      'Call to deprecated method assertElementPresent() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->elementExists() instead.',
      'Call to deprecated method assertNoText() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use - $this->assertSession()->responseNotContains() for non-HTML responses, like XML or Json. - $this->assertSession()->pageTextNotContains() for HTML responses. Unlike the deprecated assertNoText(), the passed text should be HTML decoded, exactly as a human sees it in the browser.',
      'Call to deprecated method assertNoRaw() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseNotContains() instead.',
      'Call to deprecated method assertTitle() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->titleEquals() instead.',
      'Call to deprecated method assertNoLink() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkNotExists() instead.',
      'Call to deprecated method assertLink() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkExists() instead.',
      'Call to deprecated method assertLinkByHref() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkByHrefExists() instead.',
      'Call to deprecated method assertNoLinkByHref() of class Drupal\Tests\BrowserTestBase. Deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkByHrefNotExists() instead.',

    ];
    return
      in_array($string, $rector_covered) ||
      strpos($string, 'Call to deprecated method l() of class Drupal') === 0;
  }

  /**
   * Finds all .info.yml files for non-test extensions under a path.
   *
   * @param string $path
   *   Base path to find all info.yml files in.
   *
   * @return array
   *   A list of paths to .info.yml files found under the base path.
   */
  private function getSubExtensionInfoFiles(string $path) {
    $files = [];
    foreach(glob($path . '/*.info.yml') as $file) {
      // Make sure the filename matches rules for an extension. There may be
      // info.yml files in shipped configuration which would have more parts.
      $parts = explode('.', basename($file));
      if (count($parts) == 3) {
        $files[] = $file;
      }
    }
    foreach (glob($path . '/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
      $files = array_merge($files, $this->getSubExtensionInfoFiles($dir));
    }
    return $files;
  }

}
