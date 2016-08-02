<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Controller\SearchController
 */

namespace Drupal\smt_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_admin\MemberUtils;

class SearchController extends ControllerBase {

    /**
     * Callback for /smtadmin/search
     * Forms to search by ID, name or email
     */
    public function content() {

        return array(
            'searchID' => \Drupal::formBuilder()->getForm('Drupal\smt_admin\Form\SearchIDForm'),
            'searchName' => \Drupal::formBuilder()->getForm('Drupal\smt_admin\Form\SearchNameForm'),
            'searchEmail' => \Drupal::formBuilder()->getForm('Drupal\smt_admin\Form\SearchEmailForm'),
        );

    }

    public function searchName($fname, $lname) {

        $fname = trim(urldecode($fname));
        $lname = trim(urldecode($lname));

        if ($fname === '') $fname = '-';
        if ($lname === '') $lname = '-';

        if ($fname === '-' && $lname === '-') {
            $searchUrl = Url::fromRoute('smt_admin.search', array());
            $output = 'No search terms defined. Return to the ' . \Drupal::l('Search Page', $searchUrl) . '.';
            return array(
                'markup' => array(
                    '#markup' => $output
                )
            );
        }

        $conditions = array();

        if ($fname != '-') {
            $conditions[] = array('m.fname', "%$fname%", 'LIKE');
        }
        if ($lname != '-') {
            $conditions[] = array('m.lname', "%$lname%", 'LIKE');
        }

        return self::searchResultsTable($conditions);

    }

    public function searchEmail($email) {

        $email = trim(urldecode($email));

        if (empty($email)) {
            $searchUrl = Url::fromRoute('smt_admin.search', array());
            $output = 'No search terms defined. Return to the ' . \Drupal::l('Search Page', $searchUrl) . '.';
            return array(
                'markup' => array(
                    '#markup' => $output
                )
            );
        }

        return self::searchResultsTable(array(
            array('u.mail', "%$email%", 'LIKE')
        ));

    }

    protected function searchResultsTable($conditions) {

        // sorting params
        $params = \Drupal::request()->query;
        $sort_dir = $params->get('sort');
        $sort_col = $params->get('order');

        $fields = array(
            'ID' => 'ID',
            'First name' => 'fname',
            'Last name' => 'lname',
            'E-mail' => 'mail'
        );

        $header = array(
            array('data' => t('ID'), 'field' => 'ID', 'sort' => 'asc'),
            array('data' => t('First name'), 'field' => 'fname'),
            array('data' => t('Last name'), 'field' => 'lname'),
            array('data' => t('E-mail'), 'field' => 'mail'),
        );

        $query = db_select('members', 'm');
        $query->innerJoin('users_field_data', 'u', 'u.uid = m.ID');

        foreach ($conditions as $condition) {
            if (sizeof($condition) < 3) continue;
            $query->condition($condition[0], $condition[1], $condition[2]);
        }

        $count_query = clone $query;
        $count_query->addExpression('count(m.ID)');

        $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $paged_query->limit(50);

        if (!empty($sort_dir) && !empty($fields[$sort_col])) {
            $paged_query->orderBy($fields[$sort_col], $sort_dir);
        }
        else {
            $paged_query->orderBy('ID', 'asc');
        }

        $paged_query->setCountQuery($count_query);

        // pull the fields in the specified order
        $paged_query->addField('m', 'ID', 'ID');
        $paged_query->addField('m', 'fname', 'fname');
        $paged_query->addField('m', 'lname', 'lname');
        $paged_query->addField('u', 'mail', 'mail');

        $result = $paged_query->execute();

        $rows = array();

        // add edit and delete links to rows
        foreach ($result as $row) {

            $row = (array) $row;
            $id = $row['ID'];

            $editUrl = URL::fromRoute('smt_admin.memberEdit', array('id' => $id));
            $row['ID'] = \Drupal::l($id, $editUrl);

            $emailUrl = URL::fromRoute('entity.user.edit_form', array('user' => $id));
            $row['mail'] = \Drupal::l($row['mail'], $emailUrl);

            $rows[] = array('data' => $row);
        }

        // $searchUrl = Url::fromRoute('smt_admin.search', array());
        // $output = $this->t('<br><p>Return to the ' . \Drupal::l('Search Page', $searchUrl) . '.</p>');

        return array(
            // 'markup' => array(
            //     '#type' => 'markup',
            //     '#markup' => $output,
            // ),
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

}
