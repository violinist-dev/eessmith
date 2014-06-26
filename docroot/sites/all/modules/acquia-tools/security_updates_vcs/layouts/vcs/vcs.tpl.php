<?php
/**
 * @file
 * Display Suite VCS template.
 *
 * Available variables:
 *
 * Layout:
 * - $classes: String of classes that can be used to style this layout.
 * - $contextual_links: Renderable array of contextual links.
 *
 * Regions:
 *
 * - $branch: Rendered content for the "Branch" region.
 * - $branch_classes: String of classes that can be used to style
 *     the "Branch" region.
 *
 * - $database: Rendered content for the "Database" region.
 * - $database_classes: String of classes that can be used to style
 *     the "Database" region.
 *
 * - $client: Rendered content for the "Client" region.
 * - $client_classes: String of classes that can be used to style
 *     the "Client" region.
 *
 * - $merge: Rendered content for the "Merge" region.
 * - $merge_classes: String of classes that can be used to style
 *     the "Merge" region.
 */
?>
<div class="<?php print $classes; ?>" <?php print $attributes; ?>>

  <?php if (isset($title_suffix['contextual_links'])): ?>
    <?php print render($title_suffix['contextual_links']); ?>
  <?php endif; ?>

  <?php if ($info_update): ?>
    <div class="vcs ds-infoupdate<?php print $info_update_classes; ?>">
      <?php print $info_update; ?>
    </div>
  <?php endif; ?>

 <?php if ($ahtools): ?>
    <div class="vcs ds-ahtools<?php print $ahtools_classes; ?>">
      <?php print $ahtools; ?>
    </div>
  <?php endif; ?>

 <?php if ($logschecks): ?>
    <div class="vcs ds-logschecks<?php print $logschecks_classes; ?>">
      <?php print $logschecks; ?>
    </div>
  <?php endif; ?>

    <?php if ($stage1): ?>
    <div class="vcs ds-stage1<?php print $stage1_classes; ?>">
      <?php print $stage1; ?>
    </div>
  <?php endif; ?>

 <?php if ($stage3): ?>
    <div class="vcs ds-stage3<?php print $stage3_classes; ?>">
      <?php print $stage3; ?>
    </div>
  <?php endif; ?>
 <?php if ($stage5): ?>
    <div class="vcs ds-stage5<?php print $stage5_classes; ?>">
      <?php print $stage5; ?>
    </div>
  <?php endif; ?>

  <?php if ($branch): ?>
    <div class="vcs ds-branch<?php print $branch_classes; ?>">
      <?php print $branch; ?>
    </div>
  <?php endif; ?>

  <?php if ($database): ?>
    <div class="vcs ds-database<?php print $database_classes; ?>">
      <?php print $database; ?>
    </div>
  <?php endif; ?>

  <?php if ($inform_branch): ?>
    <div class="vcs ds-informbranch<?php print $inform_branch_classes; ?>">
      <?php print $inform_branch; ?>
    </div>
  <?php endif; ?>

  <?php if ($merge): ?>
    <div class="vcs ds-merge<?php print $merge_classes; ?>">
      <?php print $merge; ?>
    </div>
  <?php endif; ?>

   <?php if ($inform_tag): ?>
    <div class="vcs ds-informtag<?php print $inform_tag_classes; ?>">
      <?php print $inform_tag; ?>
    </div>
  <?php endif; ?>
</div>
