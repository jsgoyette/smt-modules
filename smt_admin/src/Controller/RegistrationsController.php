<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Controller\RegistrationsController
 */

namespace Drupal\smt_admin\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_admin\MemberUtils;

class RegistrationsController extends ControllerBase {

    // make table a variable, since the practice is to use a
    // new copy of the smtmeetings table each year
    protected static $table = 'smtmeeting2015';

    /**
     * Callback for /smtadmin/registration2015
     */
    public function content() {

        $links = '<ul>';

        $url = Url::fromRoute('smt_admin.registrationsAll', array());
        $links .= '<li>' . \Drupal::l('All', $url) . '</li>';

        $url = Url::fromRoute('smt_admin.registrationsNew', array());
        $links .= '<li>' . \Drupal::l('New', $url) . '</li>';

        $url = Url::fromRoute('smt_admin.registrationsConfirmed', array());
        $links .= '<li>' . \Drupal::l('Confirmed', $url) . '</li>';

        $url = Url::fromRoute('smt_admin.registrationsExport', array());
        $links .= '<li>' . \Drupal::l('Export List', $url) . '</li>';

        $links .= '</ul>';

        return array(
            'markup' => array(
                '#type' => 'markup',
                '#markup' => $this->t($links),
            ),
        );
    }

    public function listAll() {

        // sorting params
        $params = \Drupal::request()->query;
        $sort_dir = $params->get('sort');
        $sort_col = $params->get('order');

        $fields = array(
            'RegID' => 'num',
            'MemID' => 'ID',
            'Last name' => 'lname',
            'First Name' => 'fname',
            'Affiliation' => 'dept',
            'Options' => 'options',
            'Guide' => 'confguide',
            'Payment' => 'payment',
            'Donation' => 'donation',
            'Status' => 'status',
        );

        $header = array(
            array('data' => t('RegID'), 'field' => 'num', 'sort' => 'asc'),
            array('data' => t('MemID'), 'field' => 'ID'),
            array('data' => t('Last Name'), 'field' => 'lname'),
            array('data' => t('First Name'), 'field' => 'fname'),
            array('data' => t('Options'), 'field' => 'options'),
            array('data' => t('Guide'), 'field' => 'confguide'),
            array('data' => t('Payment'), 'field' => 'payment'),
            array('data' => t('Donation'), 'field' => 'donation'),
            array('data' => t('Status'), 'field' => 'status'),
            array('data' => t('Dept'), 'field' => 'dept'),
            array('data' => t('Action'))
        );


        $query = db_select(self::$table, 's');
        $query->innerJoin('members', 'm', 'm.ID = s.ID');
        $query->leftJoin('smtdonations', 'd', 'd.idstr = s.idstr');
        $query->condition('s.deleted', 0, '=');

        $count_query = clone $query;
        $count_query->addExpression('count(s.num)');

        $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $paged_query->limit(50);

        if (!empty($sort_dir) && !empty($fields[$sort_col])) {
            $paged_query->orderBy($fields[$sort_col], $sort_dir);
        }

        $paged_query->setCountQuery($count_query);

        // pull the fields in the specified order
        $paged_query->addField('s', 'num', 'num');
        $paged_query->addField('s', 'ID', 'ID');
        $paged_query->addField('m', 'lname', 'lname');
        $paged_query->addField('m', 'fname', 'fname');
        $paged_query->addField('s', 'options', 'options');
        $paged_query->addField('s', 'confguide', 'confguide');
        $paged_query->addField('s', 'payment', 'payment');
        $paged_query->addField('d', 'amount', 'donation');
        $paged_query->addField('s', 'status', 'status');
        $paged_query->addField('m', 'dept', 'dept');

        $result = $paged_query->execute();

        $rows = array();

        // add edit and delete links to rows
        foreach ($result as $row) {

            $row = (array) $row;

            $row['payment'] = number_format($row['payment'], 2);
            $row['donation'] = number_format($row['donation'], 2);

            if ($row['status'] == 'new') {
                $dupUrl = Url::fromRoute('smt_admin.registrationsMarkDuplicate', array('id' => $row['num']));
                array_push($row, \Drupal::l('Mark as Duplicate', $dupUrl));
            }
            else {
                array_push($row, '');
            }

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

    public function listConfirmed() {

        // sorting params
        $params = \Drupal::request()->query;
        $sort_dir = $params->get('sort');
        $sort_col = $params->get('order');

        $fields = array(
            'RegID' => 'num',
            'MemID' => 'ID',
            'Last name' => 'lname',
            'First Name' => 'fname',
            'Affiliation' => 'dept',
            'Options' => 'options',
            'Guide' => 'confguide',
            'Payment' => 'payment',
            'Donation' => 'donation',
            'Status' => 'status',
        );

        $header = array(
            array('data' => t('RegID'), 'field' => 'num', 'sort' => 'asc'),
            array('data' => t('MemID'), 'field' => 'ID'),
            array('data' => t('Last Name'), 'field' => 'lname'),
            array('data' => t('First Name'), 'field' => 'fname'),
            array('data' => t('Options'), 'field' => 'options'),
            array('data' => t('Guide'), 'field' => 'confguide'),
            array('data' => t('Payment'), 'field' => 'payment'),
            array('data' => t('Donation'), 'field' => 'donation'),
            array('data' => t('Status'), 'field' => 'status'),
            array('data' => t('Dept'), 'field' => 'dept'),
        );


        $query = db_select(self::$table, 's');
        $query->innerJoin('members', 'm', 'm.ID = s.ID');
        $query->leftJoin('smtdonations', 'd', 'd.idstr = s.idstr');
        $query->condition('s.deleted', 0, '=');
        $query->condition('s.status', 'confirmed', '=');

        $count_query = clone $query;
        $count_query->addExpression('count(s.num)');

        $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $paged_query->limit(50);

        if (!empty($sort_dir) && !empty($fields[$sort_col])) {
            $paged_query->orderBy($fields[$sort_col], $sort_dir);
        }

        $paged_query->setCountQuery($count_query);

        // pull the fields in the specified order
        $paged_query->addField('s', 'num', 'num');
        $paged_query->addField('s', 'ID', 'ID');
        $paged_query->addField('m', 'lname', 'lname');
        $paged_query->addField('m', 'fname', 'fname');
        $paged_query->addField('s', 'options', 'options');
        $paged_query->addField('s', 'confguide', 'confguide');
        $paged_query->addField('s', 'payment', 'payment');
        $paged_query->addField('d', 'amount', 'donation');
        $paged_query->addField('s', 'status', 'status');
        $paged_query->addField('m', 'dept', 'dept');

        $result = $paged_query->execute();

        $rows = array();

        // add edit and delete links to rows
        foreach ($result as $row) {

            $row = (array) $row;

            $row['payment'] = number_format($row['payment'], 2);
            $row['donation'] = number_format($row['donation'], 2);

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

    public function listNew() {

        // sorting params
        $params = \Drupal::request()->query;
        $sort_dir = $params->get('sort');
        $sort_col = $params->get('order');

        $fields = array(
            'RegID' => 'num',
            'MemID' => 'ID',
            'Last name' => 'lname',
            'First Name' => 'fname',
            'Affiliation' => 'dept',
            'Options' => 'options',
            'Guide' => 'confguide',
            'Payment' => 'payment',
            'Donation' => 'donation',
            'Status' => 'status',
        );

        $header = array(
            array('data' => t('RegID'), 'field' => 'num', 'sort' => 'asc'),
            array('data' => t('MemID'), 'field' => 'ID'),
            array('data' => t('Last Name'), 'field' => 'lname'),
            array('data' => t('First Name'), 'field' => 'fname'),
            array('data' => t('Options'), 'field' => 'options'),
            array('data' => t('Guide'), 'field' => 'confguide'),
            array('data' => t('Payment'), 'field' => 'payment'),
            array('data' => t('Donation'), 'field' => 'donation'),
            array('data' => t('Status'), 'field' => 'status'),
            array('data' => t('Dept'), 'field' => 'dept'),
            array('data' => t('Action')),
        );


        $query = db_select(self::$table, 's');
        $query->innerJoin('members', 'm', 'm.ID = s.ID');
        $query->leftJoin('smtdonations', 'd', 'd.idstr = s.idstr');
        $query->condition('s.deleted', 0, '=');

        $group = $query->orConditionGroup()
            ->condition('s.status', 'new', '=')
            ->condition('s.status', 'paid', '=');
        $query->condition($group);

        $count_query = clone $query;
        $count_query->addExpression('count(s.num)');

        $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $paged_query->limit(50);

        if (!empty($sort_dir) && !empty($fields[$sort_col])) {
            $paged_query->orderBy($fields[$sort_col], $sort_dir);
        }

        $paged_query->setCountQuery($count_query);

        // pull the fields in the specified order
        $paged_query->addField('s', 'num', 'num');
        $paged_query->addField('s', 'ID', 'ID');
        $paged_query->addField('m', 'lname', 'lname');
        $paged_query->addField('m', 'fname', 'fname');
        $paged_query->addField('s', 'options', 'options');
        $paged_query->addField('s', 'confguide', 'confguide');
        $paged_query->addField('s', 'payment', 'payment');
        $paged_query->addField('d', 'amount', 'donation');
        $paged_query->addField('s', 'status', 'status');
        $paged_query->addField('m', 'dept', 'dept');

        $result = $paged_query->execute();

        $rows = array();

        // add edit and delete links to rows
        foreach ($result as $row) {

            $row = (array) $row;

            $row['payment'] = number_format($row['payment'], 2);
            $row['donation'] = number_format($row['donation'], 2);

            $confUrl = Url::fromRoute('smt_admin.registrationsMarkConfirmed', array('id' => $row['num']));
            array_push($row, \Drupal::l('Mark as Confirmed', $confUrl));

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

    public function markConfirmed($id) {

        $db = Database::getConnection();

        if (empty($id)) {
            drupal_set_message('Null payment ID given', 'error');
        }
        else {

            $sql = "SELECT idstr, status FROM " . self::$table . " s WHERE s.num = ?";
            $reg = $db->query($sql, array($id))->fetch();

            if (empty($reg)) {
                drupal_set_message('Payment with that ID not found', 'error');
            }
            else {
                if ($reg->status != 'new' || $reg->status != 'paid') {
                    drupal_set_message('Cannot mark payment with status "'. $reg->status . '" as confirmed', 'error');
                }
                else {
                    $sql = "UPDATE " . self::$table . " s SET status = 'confirmed' WHERE s.num = ?";
                    $db->query($sql, array($id));

                    drupal_set_message('Registration marked as confirmed');
                }
            }
        }

        return $this->redirect('smt_admin.registrationsAll', array());
    }

    public function markDuplicate($id) {

        $db = Database::getConnection();

        if (empty($id)) {
            drupal_set_message('Null payment ID given', 'error');
        }
        else {

            $sql = "SELECT idstr, status FROM " . self::$table . " s WHERE s.num = ?";
            $reg = $db->query($sql, array($id))->fetch();

            if (empty($reg)) {
                drupal_set_message('Payment with that ID not found', 'error');
            }
            else {
                if ($reg->status != 'new') {
                    drupal_set_message('Cannot mark payment with status "'. $reg->status . '" as duplicate', 'error');
                }
                else {
                    $sql = "UPDATE " . self::$table . " s SET status = 'duplicate' WHERE s.num = ?";
                    $db->query($sql, array($id));

                    $sql = "UPDATE smtdonations d SET status = 'duplicate' WHERE d.idstr = ? AND d.status <> 'pledged'";
                    $db->query($sql, array($reg->idstr));

                    drupal_set_message('Registration marked as duplicate');
                }
            }
        }

        return $this->redirect('smt_admin.registrationsAll', array());
    }

    public function export() {

        $db = Database::getConnection();
        $sql = "SELECT m.fname, m.lname, m.dept, s.payment, d.amount AS donation, u.mail,
            s.confguide, m.add1, m.add2, m.add3, m.city, m.state, m.zip, m.country, s.status
            FROM members m
            INNER JOIN users_field_data u ON m.ID = u.uid
            INNER JOIN ".self::$table." s ON m.ID = s.ID
            LEFT JOIN smtdonations d ON s.idstr=d.idstr
            WHERE (s.status = 'confirmed' OR s.status = 'paid' OR s.status = 'new') AND NOT s.deleted";

        $rows = $db->query($sql)->fetchAll();

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=SMT-Registrations-Export.csv");

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
                // $value = str_replace(',', ' ', $value);
                $value = str_replace('"', '\"', $value);
                $value = str_replace(array("\n", "\r"), ' ', $value);
                $row[$key] = '"' . $value . '"';
            }

            echo implode(',', $row) ."\r\n";
        }

        die();
    }
}
