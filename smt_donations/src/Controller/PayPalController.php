<?php

/**
 * @file
 * Contains \Drupal\smt_donations\Controller\PayPalController
 */

namespace Drupal\smt_donations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

class PayPalController extends ControllerBase {

    public function success($id) {

        $db = Database::getConnection();
        $url = Url::fromRoute('smt_donations.donations');
        $output = '<p>Click ' . \Drupal::l('here', $url) . ' to return to the SMT Donations page.</p>';

        $sql = "SELECT * FROM smtdonations WHERE idstr = ?";
        $data = $db->query($sql, array($id))->fetch();

        if (empty($data->idstr)) {
            drupal_set_message('Donation data could not be accessed! Error code EVVK07.', 'error');
            return array(
                '#type' => 'markup',
                '#markup' => $this->t($output),
            );
        }

        $output = '<br><p>Thank you for your charitable contribution! <p>' . $output;

        $name = trim($data->firstname) . ' ' . trim($data->lastname);

        $address = chr(13) . chr(10) . trim($data->address1);

        if (!empty($data->address2))
            $address .= chr(13) . chr(10) . trim($data->address2);
        if (!empty($data->city))
            $address .= chr(13) . chr(10) . trim($data->city);
        if (!empty($data->state))
            $address .= ', ' . trim($data->state);
        if (!empty($data->zip))
            $address .= ' ' . trim($data->zip);
        if (!empty($data->country))
            $address .= chr(13) . chr(10) . trim($data->country);

        $res = self::send_donation_received_email($data->email, $name, number_format($data->amount, 2), $address);
        \Drupal::logger('smt_donations')->notice($data->email);

        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );
    }


    protected function send_donation_received_email($email, $name, $amount, $address) {

        $mailManager = \Drupal::service('plugin.manager.mail');

        $key = 'donation';
        $to = 'jsgoyette@gmail.com';
        $params = array('name' => $name, 'amount' => $amount, 'address' => $address);
        $langcode = 'en';
        $send = true;

        $result = $mailManager->mail('smt_donations', $key, $to, $langcode, $params, null, $send);

        if ($result['result'] !== true) {
            return false;
        }

        return $result;
    }

    public function cancelled($id) {

        $db = Database::getConnection();
        $url = Url::fromRoute('smt_donations.donations');
        $output = '<p>You have cancelled the donation.</p>' . \Drupal::l('Return to donations', $url) . '.';

        if (!empty($id)) {
            $sql = "UPDATE smtdonations SET status = 'cancelled' WHERE idstr = ?";
            $success = $db->query($sql, array($id));
        }

        drupal_set_message('Donation cancelled', 'info');

        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );
    }

    public function content($id) {

        if (empty($id)) {
            drupal_set_message('No id. Bad URL.', 'error');
            return array(
                '#type' => 'markup',
                '#markup' => $this->t('An error occurred. If you believe that the site is malfunctioning, please contact site administration.')
            );
        }

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        $sql = "SELECT * FROM smtdonations WHERE idstr = ?";
        $data = $db->query($sql, array($id))->fetch();

        if (!$data) {
            drupal_set_message('Donation data could not be accessed! Error code EVVK01.', 'error');
            return array(
                '#type' => 'markup',
                '#markup' => $this->t(''),
            );
        }

        $transaction_code = 'ZZZA';
        $output = '<br><p>You have selected to donate $' . number_format($data->amount, 2) . ' USD.</p>';

        $output .= '<p>Click the button below to pay using PayPal. </p>';
        $output .= '<form action="https://www.paypal.com/cgi-bin/buynow" method="post">';
        $output .= '<input type="hidden" name="cmd" value="_xclick"/>';
        $output .= '<input type="hidden" name="business" value="vlong@uchicago.edu" />';
        $output .= '<input type="hidden" name="lc" value="US" />';
        $output .= '<input type="hidden" name="item_name" value="' . $transaction_code . ' - Donation to SMT #' . $data->id . ' (' . number_format($data->amount, 2) . 'USD)" />';
        $output .= '<input type="hidden" name="no_note" value="1" />';
        $output .= '<input type="hidden" name="no_shipping" value="1" />';
        $output .= '<input type="hidden" name="amount" value="' . number_format($data->amount, 2) . '" />';
        // $output .= '<input type="hidden" name="amount" value="0.01" />';
        $output .= '<input type="hidden" name="currency_code" value="USD" />';
        $output .= '<input type="hidden" name="test" value="1673SC2" />';
        $output .= '<input type="hidden" name="custom" value="donation" />';
        $output .= '<input type="hidden" name="invoice" value="'.$data->idstr.'" />';
        $output .= '<input type="hidden" name="src" value="1" />';
        $output .= '<input type="hidden" name="p3" value="1" />';
        $output .= '<input type="hidden" name="t3" value="Y" />';
        $output .= '<input type="hidden" name="sra" value="1" />';
        $output .= '<input type="hidden" name="notify_url" value="https://societymusictheory.org/ipn.php">';
        $output .= '<input type="hidden" name="bn" value="PP-SubscriptionsBF:btn_buynow_LG.gif:NonHosted" />';
        // $output .= '<input type="hidden" name="return" value="http://societymusictheory.org/smtdonations/payment/success/' . $data->idstr .'" />';
        $output .= '<input type="hidden" name="cancel_return" value="https://societymusictheory.org/donations/payment/cancelled/' . $data->idstr .'" />';
        $output .= '<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" border="0" name="submit" alt="" />';
        $output .= '<img alt="PayPal security pixel" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />';
        $output .= '</form><br />';

        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );

    }
}
