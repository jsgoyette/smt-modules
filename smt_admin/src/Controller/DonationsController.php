<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Controller\DonationsController
 */

namespace Drupal\smt_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_admin\MemberUtils;

class DonationsController extends ControllerBase {

    /**
     * Callback for /smtadmin/donations.
     * Create a paged list of all donations
     * With links for donations detail views
     */
    public function donationList() {

        // sorting params
        $params = \Drupal::request()->query;
        $sort_dir = $params->get('sort');
        $sort_col = $params->get('order');

        $fields = array(
            'ID' => 'id',
            'Last Name' => 'lastname',
            'First Name' => 'firstname',
            'E-mail' => 'email',
            'Amount' => 'amount',
            'Status' => 'status',
            'Date' => 'created',
            'Pledge Date' => 'pledge_date',
        );

        $header = array(
            array('data' => t('ID'), 'field' => 'ID', 'sort' => 'asc'),
            array('data' => t('Last Name'), 'field' => 'lastname'),
            array('data' => t('First Name'), 'field' => 'firstname'),
            array('data' => t('E-mail'), 'field' => 'email'),
            array('data' => t('Amount'), 'field' => 'amount'),
            array('data' => t('Status'), 'field' => 'status'),
            array('data' => t('Date'), 'field' => 'created'),
            array('data' => t('Pledge Date'), 'field' => 'pledge_date'),
        );

        $query = db_select('smtdonations', 'd');

        $count_query = clone $query;
        $count_query->addExpression('count(d.id)');

        $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $paged_query->limit(50);

        if (!empty($sort_dir) && !empty($fields[$sort_col])) {
            $paged_query->orderBy($fields[$sort_col], $sort_dir);
        }

        $paged_query->setCountQuery($count_query);

        // pull the fields in the specified order
        $paged_query->addField('d', 'id', 'id');
        $paged_query->addField('d', 'lastname', 'lastname');
        $paged_query->addField('d', 'firstname', 'firstname');
        $paged_query->addField('d', 'email', 'email');
        $paged_query->addField('d', 'amount', 'amount');
        $paged_query->addField('d', 'status', 'status');
        $paged_query->addField('d', 'created', 'created');
        $paged_query->addField('d', 'pledge_date', 'pledge_date');

        $result = $paged_query->execute();

        $rows = array();

        // add edit and delete links to rows
        foreach ($result as $row) {

            $row = (array) $row;

            $detailUrl = Url::fromRoute('smt_admin.donationDetail', array('id' => $row['id']));
            $row['id'] = \Drupal::l($row['id'], $detailUrl);
            $row['amount'] = '$' . number_format($row['amount'], 2);
            $row['created'] = date('Y-m-d', strtotime($row['created']));

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
     * Callback for /smtadmin/donations/detail/{id}
     */
    public function donationDetail($id) {

        $db = Database::getConnection();

        if (empty($id)){
            drupal_set_message('Null donation ID given. Cannot proceed.', 'error');
            return array();
        }

        $sql = "SELECT * FROM smtdonations d WHERE d.id = ?";
        $donation = $db->query($sql, array($id))->fetch();

        if(empty($donation)) {
          drupal_set_message('Donation with that ID not found', 'error');
          return array();
        }

        $output = '<table class="table table-bordered table-striped">';
        $output .= '<tr><td><b>Amount</b></td><td><b>$' . number_format($donation->amount, 2, '.', ',') . '</b></td></tr>';
        $output .= '<tr><td>Last name</td><td>' . $donation->lastname . '</td></tr>';
        $output .= '<tr><td>First name</td><td>' . $donation->firstname . '</td></tr>';
        $output .= '<tr><td>SMT member</td><td>' . ($donation->member ? 'yes' : 'no') . '</td></tr>';
        $output .= '<tr><td>Address line 1</td><td>' . $donation->address1 . '</td></tr>';
        $output .= '<tr><td>Address line 2</td><td>' . $donation->address2 . '</td></tr>';
        $output .= '<tr><td>City</td><td>' . $donation->city . '</td></tr>';
        $output .= '<tr><td>State</td><td>' . $donation->state . '</td></tr>';
        $output .= '<tr><td>Zip</td><td>' . $donation->zip . '</td></tr>';
        $output .= '<tr><td>Country</td><td>' . $donation->country . '</td></tr>';
        $output .= '<tr><td>Status</td><td>' . $donation->status . '</td></tr>';
        $output .= '<tr><td>Transaction ID</td><td>' . $donation->transaction_id . '</td></tr>';
        $output .= '</table>';

        if (($donation->status == 'new') || ($donation->status == 'pledged')) {
            $url = Url::fromRoute('smt_admin.donationConfirm', array('id' => $id));
            $url->setOptions(array(
                'attributes' => array(
                    'class' => array('btn', 'btn-primary'),
                ),
            ));
            $output .= \Drupal::l(t('Mark as Paid'), $url);
        }

        return array(
            'markup' => array(
                '#type' => 'markup',
                '#markup' => $this->t($output),
            ),
        );
    }

    /**
     * Callback for /smtadmin/donations/confirm/{id}
     */
    public function donationConfirm($id) {

        $db = Database::getConnection();

        if (empty($id)) {
            drupal_set_message('Null donation ID given. Cannot proceed.', 'error');
        }
        else {
            $sql = "SELECT status FROM smtdonations d WHERE d.id = ?";
            $donation = $db->query($sql, array($id))->fetch();

            if (empty($donation)) {
                drupal_set_message('Donation with that ID not found', 'error');
            }
            else {
                if (($donation->status != 'new') && ($donation->status != 'pledged')) {
                    drupal_set_message('Cannot process payment with status '. $donation->status, 'error');
                }
                else {
                    $sql = "UPDATE smtdonations d SET status = 'paid' WHERE d.id = ?";
                    $res = $db->query($sql, array($id));
                    drupal_set_message('Donation marked as paid!');
                }
            }
        }

        return $this->redirect('smt_admin.donationDetail', array('id' => $id));
    }
}
