; Specify the version of Drupal being used.
core = 8.x
; Specify the api version of Drush Make.
api = 2

; Patching ActionsDropbutton.php in bootstrap theme.
projects[bootstrap][patch][] = "https://www.drupal.org/files/issues/2657124-18.patch"