<?php
/**
 * @file
 * Contains \Drupal\smt_test\Controller\MainController
 */

namespace Drupal\smt_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class MainController extends ControllerBase {
    public function content() {
        $connection = Database::getConnection();
        $id = 18;
        $result = $connection->query("SELECT * FROM users WHERE uid = :uid", array(
            'uid' => $id
        ))->fetchAll();

        $output = 'Hello, World!';

        if ($result) {
            foreach ($result as $record) {
                // Perform operations on $record->title, etc. here.
                $output .= '<pre>' . print_r($record, true) . '</pre>';
            }
        }
        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );
    }
}
