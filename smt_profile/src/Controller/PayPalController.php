<?php

/**
 * @file
 * Contains \Drupal\smt_profile\Controller\PayPalController
 */

namespace Drupal\smt_profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

class PayPalController extends ControllerBase {

    public function success() {
        $url = Url::fromRoute('smt_profile.welcome');
        $output = 'We have received your payment. Please allow a few days for activating your account. '
            . \Drupal::l('Return to your profile', $url) . '.';
        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );
    }

    public function cancelled() {
        $url = Url::fromRoute('smt_profile.welcome');
        $output = 'Payment cancelled. ' . \Drupal::l('Return to your profile', $url) . '.';
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

        $sql = "SELECT * FROM smtpayment p WHERE p.id = :id";
        $data = $db->query($sql, array(':id' => $id))->fetch();

        if (!$data) {
            drupal_set_message('Registration not found.', 'error');
            return array(
                '#type' => 'markup',
                '#markup' => $this->t('An error occurred. If you believe that the site is malfunctioning, please contact site administration.')
            );
        }

        $membertypeoptions = array(
            'regular' => t('SMT membership, regular'),
            'student' => t('SMT membership, student'),
            'retired' => t('SMT membership, retired'),
            'joint' => t('Joint SMT membership, regular'),
            'jointstudent' => t('Joint SMT membership, student'),
            'jointretired' => t('Joint SMT membership, retired'),
            'subsidized'=> t('SMT membership, subsidized'),
            'overseas' => t('SMT membership, overseas and member of other professional music society (75.00)'),
            'jointoverseas' => t('Joint SMT membership, overseas and member of other professional music society (85.00)')
        );

        $membertypeoptionscode = array(
            'regular' => 'A',
            'student' => 'C',
            'retired' => 'E',
            'joint' => 'B',
            'jointstudent' => 'D',
            'jointretired' => 'F',
            'subsidized' => 'G',
            'overseas' => 'H',
            'jointoverseas' => 'I'
        );

        $membertypeprices = array(
            'regular' => 85.00,
            'student' => 40.00,
            'retired' => 40.00,
            'joint' => 95.00,
            'jointstudent' => 50.00,
            'jointretired' => 50.00,
            'subsidized' => 40.00,
            'overseas' => '75.00',
            'jointoverseas' => '85.00'
        );

        $shippingoptions = array(
            '' => t(''),
            'print' => t('Ship print copy'),
            'noprint' => t('Do not ship a print copy')
        );

        $shippingoptionscode = array(
            '' => 'Z',
            'print' => 'A',
            'noprint' => 'C'
        );

        // generate transaction code
        // code array begins with 'A' since transaction type = 'membership'
        $transaction_code = 'A' . $membertypeoptionscode[$data->type] . $shippingoptionscode[$data->shipping] . 'Z';

        $membershipdescription = $membertypeoptions[$data->type];
        if ($membershipdescription == '') {
            drupal_set_message('No membership type.', 'error');
            return array(
                '#type' => 'markup',
                '#markup' => $this->t('An error occurred. If you believe that the site is malfunctioning, please contact site administration')
            );
        }
        if ($data->joint == 'joint') {
            $membershipdescription .= '. Joint member ' . $data->jointfname . ' ' . $data->jointlname . ' (UID# ' . $data->jointid . ')';
        }

        if ($shippingoptions[$data->shipping] == '') {
            drupal_set_message('No shipping options.', 'error');
            return array(
                '#type' => 'markup',
                '#markup' => $this->t('An error occurred. If you believe that the site is malfunctioning, please contact site administration')
            );
        }

        $paymentvalue = $membertypeprices[$data->type];
        if ($paymentvalue == 0) {
            drupal_set_message('No payment.', 'error');
            return array(
                '#type' => 'markup',
                '#markup' => $this->t('An error occurred. If you believe that the site is malfunctioning, please contact site administration')
            );
        }

        // if ($data->shipping == 'overseas') {
          // $paymentvalue = $paymentvalue + 15;
        // }

        $membershipdescription .= '. ' . $shippingoptions[$data->shipping] . '. Amount to be charged is ' . $paymentvalue . 'USD.';

        $output = '<br/><p>' . $membershipdescription . '</p>';
        $output .= '<form action="https://www.paypal.com/cgi-bin/buynow" method="post">';
        $output .= '<input type="hidden" name="cmd" value="_xclick"/>';
        $output .= '<input type="hidden" name="business" value="vlong@uchicago.edu" />';
        $output .= '<input type="hidden" name="lc" value="US" />';
        $output .= '<input type="hidden" name="item_name" value="' . $transaction_code . ' - UID# ' . $uid . ' ' . $membershipdescription . '" />';
        $output .= '<input type="hidden" name="no_note" value="1" />';
        $output .= '<input type="hidden" name="no_shipping" value="1" />';
        $output .= '<input type="hidden" name="amount" value="' . $paymentvalue . '" />';
        $output .= '<input type="hidden" name="currency_code" value="USD" />';
        $output .= '<input type="hidden" name="src" value="1" />';
        $output .= '<input type="hidden" name="p3" value="1" />';
        $output .= '<input type="hidden" name="t3" value="Y" />';
        $output .= '<input type="hidden" name="sra" value="1" />';
        // $output .= '<input type="hidden" name="bn" value="PP-SubscriptionsBF:btn_buynow_LG.gif:NonHosted" />';
        $output .= '<input type="hidden" name="bn" value="PP-BuyNow:btn_buynow_LG.gif:NonHosted" />';
        $output .= '<input type="hidden" name="return" value="https://societymusictheory.org/smtprofile/membership/success/" />';
        $output .= '<input type="hidden" name="cancel_return" value="https://societymusictheory.org/smtprofile/membership/cancelled" />';
        $output .= '<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" border="0" name="submit" alt="" />';
        $output .= '<img alt="PayPal security pixel" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />';
        $output .= '</form><br />';


        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );

    }
}

/*

PAYPAL TRANSACTION CODE SCHEME
******************************

POSITION 1 — transaction type
A Membership
B Conference Registration
C Conference Income (display ads, etc.)  [I'm not sure how these are entered]
Z Other

POSITION 2 — member type
A Regular
B Regular-Joint [used only for membership transactions]
C Student
D Student-Joint [used only for membership transactions]
E Retired
F Retired-Joint [used only for membership transactions]
G Subsidized
H Overseas
I Overseas-Joint
Z Other/Not applicable

POSITION 3 — shipping (A-C used only for membership transactions)
A Ship a copy
#B Ship a copy outside of North America
C Do not ship a print copy
Z Not applicable (for any transaction other than a membership)

POSITION 4 — Donation [be sure the amount continued to appear in the Item Title]
A Transaction includes a donation
Z Not applicable (transaction does not include a donation)

*/
