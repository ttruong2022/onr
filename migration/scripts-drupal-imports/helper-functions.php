<?php

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Utility: find term by name and vid.
 * @param null $name
 *  Term name
 * @param null $vid
 *  Term vid
 * @return int
 *  Term id or 0 if none.
 */
function getTidByName($name = NULL, $vid = NULL) {
  // Set name properties.
  $properties['name'] = $name;
  $properties['vid'] = $vid;

  try {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($properties);

    // create term if missing
//    if (empty($terms)) {
//      echo "Term missing and will be created: $name ($vid)" . PHP_EOL;
//      $term = \Drupal::entityTypeManager()
//        ->getStorage('taxonomy_term')
//        ->create($properties);
//      $term->save();
//      $terms = [$term];
//    }
  } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
    echo 'Error getting term' . PHP_EOL;
  }

  $term = reset($terms);
  return !empty($term) ? $term->id() : 0;
}

function getTidByNameLoop($string, $name) {
  $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($name, 0, 1, TRUE);

  $result = '';
  foreach ($tree as $term) {
    if ($term->getName() === $string) {
      $result = $term->id();
      break;
    }
  }
  return $result;
}


function getGroup($url): string {
  $group = match (TRUE) {
    str_contains($url, '/science-technology/departments/code-31') => 'ONR Code 31',
    str_contains($url, '/science-technology/departments/code-32') => 'ONR Code 32',
    str_contains($url, '/science-technology/departments/code-33') => 'ONR Code 33',
    str_contains($url, '/science-technology/departments/code-34') => 'ONR Code 34',
    str_contains($url, '/science-technology/departments/code-35') => 'ONR Code 35',
    str_contains($url, '/science-technology/departments/code-36') => 'ONR Code 36',
    str_contains($url, '/work-with-us/funding-opportunities'), str_contains($url, '/work-with-us/how-to-apply'), str_contains($url, '/work-with-us/manage-your-award') => 'Contracts and Grants',
    str_contains($url, '/work-with-us/center-for-naval-analyses') => 'Center for Naval Analyses',
    str_contains($url, '/work-with-us/navy-mantech') => 'ManTech',
    str_contains($url, '/education-Outreach') => 'ONR Education and Outreach',
    str_contains($url, '/conference-event-onr') => 'ONR Events',
    str_contains($url, '/science-technology/onr-global') => 'ONR Global',
    str_contains($url, '/about-onr/inspector-general') => 'ONR IG',
    str_contains($url, '/freedom-of-information-act-foia') => 'ONR Privacy Office',
    str_contains($url, '/science-technology/naval-reservist-component') => 'ONR Reserve Component',
    str_contains($url, '/work-with-us/technology-transfer-t2') => 'ONR T2',
    str_contains($url, '/work-with-us/small-business') => 'OSBP',
    str_contains($url, '/work-with-us/sbir-and-sttr') => 'SBIR/STTR',
    str_contains($url, '/work-with-us/rapid-innovation-fund') => 'Rapid Innovation Fund',
    default => 'CSC',
  };
  return getTidByName($group, 'groups');
}
