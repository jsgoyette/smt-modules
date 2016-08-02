<?php

/**
 * @file
 * Contains \Drupal\smt_directory\Controller\DirectoryController
 */

namespace Drupal\smt_directory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

class DirectoryController extends ControllerBase {

    /**
     * Callback for /smtdirectory.
     */
    public function directory() {

        // sorting params
        $params = \Drupal::request()->query;
        $sort_dir = $params->get('sort');
        $sort_col = $params->get('order');

        $fields = array(
            'Last name' => 'lname',
            'First name' => 'fname',
            'Affiliation' => 'dept',
            'E-mail' => 'mail'
        );

        $header = array(
            array('data' => t('Last name'), 'field' => 'lname', 'sort' => 'asc'),
            array('data' => t('First name'), 'field' => 'fname'),
            array('data' => t('E-mail'), 'field' => 'mail'),
            array('data' => t('Affiliation'), 'field' => 'dept'),
            // array('data' => t('')),
        );

        $query = db_select('members', 'm');
        $query->innerJoin('users_field_data', 'u', 'u.uid = m.ID');
        $query->condition('m.smtdirectory', 1, '=');

        $count_query = clone $query;
        $count_query->addExpression('count(m.ID)');

        $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $paged_query->limit(50);

        if (!empty($sort_dir) && !empty($fields[$sort_col])) {
            $paged_query->orderBy($fields[$sort_col], $sort_dir);
        }

        $paged_query->setCountQuery($count_query);

        // pull the fields in the specified order
        $paged_query->addField('m', 'lname', 'lname');
        $paged_query->addField('m', 'fname', 'fname');
        $paged_query->addField('u', 'mail', 'mail');
        $paged_query->addField('m', 'dept', 'dept');

        $result = $paged_query->execute();

        $rows = array();

        // add edit and delete links to rows
        foreach ($result as $row) {

            $row = (array) $row;

            // $viewUrl = Url::fromRoute('smt_directory.view', array('id' => $row['ID']));
            // array_push($row, \Drupal::l('view', $viewUrl));

            $rows[] = array('data' => $row);
        }

        $profileUrl = Url::fromRoute('smt_profile.profile');
        $output = '<p>This directory is available only to SMT members. ';
        $output .= 'The directory includes those SMT members who have expressed their wish to be listed in the SMT Member Directory. ';
        $output .= 'If you cannot see your name here, please ensure in your ';
        $output .= \Drupal::l('profile', $profileUrl);
        $output .= ' that the checkbox "Publish my information in the SMT Member Directory" is checked.<p/>';

        return array(
            'markup' => array(
                '#type' => 'markup',
                '#markup' => $this->t($output),
            ),
            'pager1' => array(
                '#type' => 'pager'
            ),
            'pager_table' => array(
                '#theme' => 'table',
                '#header' => $header,
                '#rows' => $rows,
                '#empty' => t('No records found'),
            ),
            'pager2' => array(
                '#type' => 'pager'
            )
        );
    }

    public function lists() {

        $url = Url::fromRoute('smt_directory.export_csv');
        $output = '<p>Download the SMT Member Directory in CSV (comma separated) format.';
        $output .= ' This list is for the private use of SMT members only.</p>';
        $output .= \Drupal::l('SMT Member Directory in CSV-file', $url);

        return array(
            'markup' => array(
                '#type' => 'markup',
                '#markup' => $this->t($output),
            ),
        );
    }

    public function exportCsv() {

        $db = Database::getConnection();

        $today = date('Y-m-d');
        $filename = 'smt-directory-' . $today . '.csv';

        $sql = "SELECT lname AS 'Last Name', fname AS 'First Name', dept AS Affiliation, u.mail AS Email
            FROM members m
            INNER JOIN users_field_data u ON m.ID = u.uid
            WHERE m.smtdirectory = 1
            ORDER BY lname";

        $rows = $db->query($sql)->fetchAll();

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename={$filename}");

        // build header
        if (sizeof($rows)) {
            $header = array();
            foreach ($rows[0] as $fieldName => $value) {
                $header[] = $fieldName;
            }
            echo implode(',', $header) ."\r\n";
        }

        foreach ($rows as $idx => $row) {

            $row = (array) $row;

            foreach ($row as $key => $value) {
                $value = Html::decodeEntities(strip_tags($value));
                $value = str_replace(',', ' ', $value);
                $value = str_replace(array("\n", "\r"), ' ', $value);
                $row[$key] = $value;
            }

            echo implode(',', $row) ."\r\n";
        }

        die();
    }
}
