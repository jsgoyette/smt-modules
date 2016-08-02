<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Controller\MembersController
 */

namespace Drupal\smt_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_admin\MemberUtils;

class MembersController extends ControllerBase {

    /**
     * Callback for /smtadmin.
     * Create a paged list of all SMT members from the SMT members database.
     * Create links to edit and to delete that person
     */
    public function memberList() {

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
            array('data' => t('')),
            array('data' => t(''))
        );

        $query = db_select('members', 'm');
        $query->innerJoin('users_field_data', 'u', 'u.uid = m.ID');
        $query->condition('m.ID', 1, '>');

        $count_query = clone $query;
        $count_query->addExpression('count(m.ID)');

        $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $paged_query->limit(50);

        if (!empty($sort_dir) && !empty($fields[$sort_col])) {
            $paged_query->orderBy($fields[$sort_col], $sort_dir);
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

            $editUrl = Url::fromRoute('smt_admin.memberEdit', array('id' => $row['ID']));
            array_push($row, \Drupal::l('edit', $editUrl));

            $deleteUrl = Url::fromRoute('smt_admin.memberDelete', array('id' => $row['ID']));
            array_push($row, \Drupal::l('delete', $deleteUrl));

            $rows[] = array('data' => $row);
        }

        return array(
            'markup' => array(
                '#type' => 'markup',
                '#markup' => $this->t(''),
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

    /**
     * Callback for /smtadmin/pendingmembers.
     * List of smtprofiles
     */
    public function pendingList() {

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
            array('data' => t('')),
        );

        $query = db_select('smtprofiles', 'p');
        $query->innerJoin('users_field_data', 'u', 'u.uid = p.ID');
        $query->leftJoin('members', 'm', 'm.ID = p.ID');
        $query->condition('p.ID', 1, '>');
        $query->isNull('m.ID');

        $count_query = clone $query;
        $count_query->addExpression('count(m.ID)');

        $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $paged_query->limit(50);

        if (!empty($sort_dir) && !empty($fields[$sort_col])) {
            $paged_query->orderBy($fields[$sort_col], $sort_dir);
        }

        $paged_query->setCountQuery($count_query);

        // pull the fields in the specified order
        $paged_query->addField('p', 'ID', 'ID');
        $paged_query->addField('p', 'fname', 'fname');
        $paged_query->addField('p', 'lname', 'lname');
        $paged_query->addField('u', 'mail', 'mail');

        $result = $paged_query->execute();

        $rows = array();

        // add edit and delete links to rows
        foreach ($result as $row) {

            $row = (array) $row;

            $addUrl = Url::fromRoute('smt_admin.memberAdd', array('id' => $row['ID']));
            array_push($row, \Drupal::l('add', $addUrl));

            $rows[] = array('data' => $row);
        }

        return array(
            'markup' => array(
                '#type' => 'markup',
                '#markup' => '<div class="well">This view enumerates people that have created an account and a SMT profile, but are not yet SMT members. Click to add the person to the SMT member database.</div>',
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

    /**
     * Callback for /smtadmin/lapsedmembers.
     * List of all SMT members whose renewal date has passed
     */
    public function lapsedList() {

        // sorting params
        $params = \Drupal::request()->query;
        $sort_dir = $params->get('sort');
        $sort_col = $params->get('order');

        $fields = array(
            'ID' => 'ID',
            'First name' => 'fname',
            'Last name' => 'lname',
            'E-mail' => 'mail',
            'Created' => 'date_created',
            'Expiration' => 'Date_renewed',
        );

        $header = array(
            array('data' => t('ID'), 'field' => 'ID', 'sort' => 'asc'),
            array('data' => t('First name'), 'field' => 'fname'),
            array('data' => t('Last name'), 'field' => 'lname'),
            array('data' => t('E-mail'), 'field' => 'mail'),
            array('data' => t('Created'), 'field' => 'date_created'),
            array('data' => t('Expiration'), 'field' => 'Date_renewed'),
            array('data' => t('')),
            array('data' => t('')),
        );

        $query = db_select('members', 'm');
        $query->innerJoin('users_field_data', 'u', 'u.uid = m.ID');
        $query->condition('m.ID', 1, '>');
        $query->condition('m.Date_renewed', date('Y-m-d'), '<');

        $count_query = clone $query;
        $count_query->addExpression('count(m.ID)');

        $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $paged_query->limit(50);

        if (!empty($sort_dir) && !empty($fields[$sort_col])) {
            $paged_query->orderBy($fields[$sort_col], $sort_dir);
        }

        $paged_query->setCountQuery($count_query);

        // pull the fields in the specified order
        $paged_query->addField('m', 'ID', 'ID');
        $paged_query->addField('m', 'fname', 'fname');
        $paged_query->addField('m', 'lname', 'lname');
        $paged_query->addField('u', 'mail', 'mail');
        $paged_query->addField('m', 'date_created', 'date_created');
        $paged_query->addField('m', 'Date_renewed', 'Date_renewed');

        $result = $paged_query->execute();

        $rows = array();

        // add edit and delete links to rows
        foreach ($result as $row) {

            $row = (array) $row;

            $editUrl = Url::fromRoute('smt_admin.memberEdit', array('id' => $row['ID']));
            array_push($row, \Drupal::l('edit', $editUrl));

            $deleteUrl = Url::fromRoute('smt_admin.memberDelete', array('id' => $row['ID']));
            array_push($row, \Drupal::l('delete', $deleteUrl));

            $rows[] = array('data' => $row);
        }

        return array(
            'markup' => array(
                '#type' => 'markup',
                '#markup' => '<div class="well">This view enumerates people with expired memberships.</div>',
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
}
