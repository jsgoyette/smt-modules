<?php

/**
 * @file
 * Contains \Drupal\smt_profile\Controller\RegistrationController
 */

namespace Drupal\smt_profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_profile\MemberUtils;

class RegistrationController extends ControllerBase {

    public function content() {

        // \Drupal::service('plugin.manager.mail')->mail('smt_profile', 'verification', 'jsgoyette@gmail.com', 'en', array());
        // die();

        $uid = \Drupal::currentUser()->id();
        $member = MemberUtils::member();
        $active_member = MemberUtils::is_active_member();

        $conf_date = strtotime('2016-11-01');
        $expiration_date = strtotime($member->Date_renewed);

        $output = '';
        $url = Url::fromRoute('smt_profile.membership');

        if (!$member) {
            $output = '<br><br><p>Only SMT members can register for the conference.</p>';
            $output .= '<p>Click ' . \Drupal::l('here', $url) . ' to join SMT.</p>';
        }
        else if (!$active_member || $member->payment_status != 'Completed') {
            $output = '<br><br><p>Only active SMT members can register for the conference.</p>';
            $output .= '<p>Click ' . \Drupal::l('here', $url) . ' to renew your membership.</p>';
        }
        else if ($expiration_date < $conf_date) {
            $output = '<br><br><p>Your SMT membership will expire before the conference. Please renew your membership before registering.</p>';
            $output .= '<p>Click ' . \Drupal::l('here', $url) . ' to renew your membership.</p>';
            $output .= '<p>If you have just recently renewed your membership, please check back again, as there may be a delay while records are updated.</p>';
        }

        if ($output) {
            return array(
                '#type' => 'markup',
                '#markup' => $this->t($output),
            );
        }

        $url = Url::fromRoute('smt_profile.conference_registration_info');

        $output = '<h3>Welcome to the 2015 Conference Registration site</h3>';
        $output .= '<p></p>';
        $output .= '<p>Registration is available online, however if you prefer to mail/fax a paper registration form please click ';
        $output .= '<a href="files/registration_St_Louis_final.pdf">here</a>. You will be able to download the registration form and either mail it or fax it to the address listed on the form. A receipt will be sent by e-mail once your payment has been received.</p>';
        $output .= '<p></p>';
        $output .= '<p>To register online click '.\Drupal::l('here', $url).'.</p>';
        $output .= '<p></p>';
        $output .= '<p>Once you have completed the registration process a confirmation message will appear on this page and you will be sent a receipt via e-mail.</p>';
        $output .= '<p></p>';
        $output .= '<p>If you have any questions please contact <a href="mailto:vlong@uchicago.edu">vlong@uchicago.edu</a>.</p><hr>';

        $header = array(
            array('data' => t('Registration ID'), 'field' => 'num', 'sort' => 'asc'),
            array('data' => t('Options'), 'field' => 'options'),
            array('data' => t('Guide'), 'field' => 'confguide'),
            array('data' => t('Amount'), 'field' => 'payment'),
            array('data' => t('Donation'), 'field' => 'donation'),
            array('data' => t('Status'), 'field' => 'status')
        );

        $query = db_select('smtmeeting2015', 's');
        $query->leftJoin('smtdonations', 'd', 'd.idstr = s.idstr');
        $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $query->condition('s.ID', $uid);
        $query->condition('s.deleted', 0);

        // pull the fields in the specified order
        $query->addField('s', 'num', 'num');
        $query->addField('s', 'options', 'options');
        $query->addField('s', 'confguide', 'confguide');
        $query->addField('s', 'payment', 'payment');
        $query->addField('d', 'amount', 'donation');
        $query->addField('s', 'status', 'status');

        $result = $query
            // ->limit(50)
            // ->orderBy('d.dfid')
            ->execute();

        $rows = array();

        foreach ($result as $row) {
            // extra formatting could go here
            $rows[] = array('data' => (array) $row);
        }

        if (sizeof($rows) > 0) {
            $output .= '<p>If you have already registered, it is shown in the table below. ';
            $output .= 'A new registration is created every time you proceed from this page. ';
            $output .= 'Registration becomes paid when you return from PayPal to our service after payment. ';
            $output .= 'When we receive the payment from PayPal, the status becomes confirmed. </p>';
        }

        return array(
            'markup' => array(
                '#type' => 'markup',
                '#markup' => $this->t($output),
            ),
            'pager_table' => array(
                '#theme' => 'table',
                '#header' => $header,
                '#rows' => $rows,
                '#empty' => t('There are no date formats found in the db'),
            ),
            'pager_pager' => array(
                '#theme' => 'pager'
            )
        );
    }

    public function payment($idstr) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        $sql = "SELECT s.*, d.amount as donation FROM smtmeeting2015 s
            LEFT JOIN smtdonations d ON s.idstr=d.idstr
            WHERE s.ID = ? AND s.idstr = ?";

        $data = $db->query($sql, array($uid, $idstr))->fetch();

        if (!$data) {
            drupal_set_message('Registration data could not be accessed! Error code EVRK06.', 'error');
            return '';
        }

        $registrationtype = $data->options;
        $paymentvalue = $data->payment;
        $regid = $data->num;

        $description = $registrationtype . ' (UID #' . $uid . ', registration id ' . $regid;
        if ($data->donation > 0) {
            $paymentvalue += $data->donation;
            $description .= ', donation ' . number_format($data->donation, 2) . 'USD';
        }
        $description .= ')';

        $registrationtypecode = array(
            'SMT member, regular' => 'A',
            'SMT member, student' => 'C',
            'SMT member, retired' => 'E'
        );

        $transaction_code = 'B' . $registrationtypecode[$registrationtype] . 'ZZ';

        $output = '<p>You have selected the following registration: ' . $registrationtype . '.</p>';
        if ($data->donation > 0) {
            $output .= '<p>You have selected to donate ' . number_format($data->donation, 2) . 'USD</p>';
        }
        $output .= '<p>Click the button below to pay using PayPal. </p>';
        $output .= '<form action="https://www.paypal.com/cgi-bin/buynow" method="post">';
        $output .= '<input type="hidden" name="cmd" value="_xclick"/>';
        $output .= '<input type="hidden" name="business" value="vlong@uchicago.edu" />';
        $output .= '<input type="hidden" name="lc" value="US" />';
        $output .= '<input type="hidden" name="item_name" value="' . $transaction_code . ' - ' . $description . '" />';
        $output .= '<input type="hidden" name="no_note" value="1" />';
        $output .= '<input type="hidden" name="no_shipping" value="1" />';
        $output .= '<input type="hidden" name="amount" value="' . number_format($paymentvalue, 2) . '" />';
        $output .= '<input type="hidden" name="currency_code" value="USD" />';
        $output .= '<input type="hidden" name="src" value="1" />';
        $output .= '<input type="hidden" name="p3" value="1" />';
        $output .= '<input type="hidden" name="t3" value="Y" />';
        $output .= '<input type="hidden" name="sra" value="1" />';
        $output .= '<input type="hidden" name="custom" value="registration" />';
        $output .= '<input type="hidden" name="invoice" value="'.$idstr.'" />';
        $output .= '<input type="hidden" name="bn" value="PP-SubscriptionsBF:btn_buynow_LG.gif:NonHosted" />';
        $output .= '<input type="hidden" name="notify_url" value="https://societymusictheory.org/ipn.php">';
        $output .= '<input type="hidden" name="return" value="https://societymusictheory.org/smtprofile/registration2015/success/' . $idstr .'" />';
        $output .= '<input type="hidden" name="cancel_return" value="https://societymusictheory.org/smtprofile/registration2015/cancelled/' . $idstr .'" />';
        $output .= '<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" border="0" name="submit" alt="" />';
        $output .= '<img alt="PayPal security pixel" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />';
        $output .= '</form><br />';

        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );

    }

    public function payment_cancelled($idstr) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        if ($idstr != '') {
            $sql = "UPDATE smtmeeting2015 s SET status = 'cancelled' WHERE s.ID = ? AND s.idstr = ?";
            $success = $db->query($sql, array($uid, $idstr));

            $sql = "UPDATE smtdonations d SET status = 'cancelled' WHERE d.userid = ? AND d.idstr = ? AND status<>'pledged'";
            $success = $db->query($sql, array($uid, $idstr));
        }

        drupal_set_message('Payment cancelled', 'error');

        $url = Url::fromRoute('smt_profile.welcome');

        $output = 'You have cancelled the payment. ';
        $output .= 'Click ' . \Drupal::l('here', $url) . ' to return to your profile.';

        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );

    }

    public function payment_success($idstr) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        $sql = "SELECT * FROM smtmeeting2015 s WHERE s.ID = ? AND s.idstr = ?";
        $data = $db->query($sql, array($uid, $idstr))->fetch();

        if (!$data) {
            drupal_set_message('Registration data could not be accessed! Error code EVVK07.','error');
            return '';
        }

        // check
        $sql = "SELECT * FROM smtdonations s WHERE s.userid = ? AND s.idstr = ? AND status='pledged'";
        $data = $db->query($sql, array($uid, $idstr))->fetch();

        if ($data && $data->status == 'pledged') {
            $this->send_pledgedfulfill_mail($data);
        }

        $sql = "UPDATE smtdonations d SET status = 'paid' WHERE d.userid = ? AND d.idstr = ?";
        $success = $db->query($sql, array($uid, $idstr));
        $sql = "UPDATE {smtmeeting2015} s SET status = 'paid' WHERE s.ID = ? AND s.idstr = ?";
        $success = $db->query($sql, array($uid, $idstr));

        if ($success) {
            drupal_set_message('Registration completed successfully');
            $this->send_registration2015_mail();
            return array(
                '#type' => 'markup',
                '#markup' => $this->t('Thank you for registering for the SMT Meeting. We will send you an email when your payment has been processed.'),
            );
        } else {
            drupal_set_message('Registration data could not be accessed! Error code EVVK08.','error');
            return '';
        }
    }

    /**
     * Function sends a verification email to the user (using $user->mail).
     */
    private function send_pledgedfulfill_mail($data)
    {
        $mailManager = \Drupal::service('plugin.manager.mail');

        $key = 'pledgefulfilled';
        $to = 'lisamargulis@gmail.com';
        $params = array('donation_id' => $data->id);
        $langcode = 'en';
        $send = true;

        $result = $mailManager->mail('smt_profile', $key, $to, $langcode, $params, null, $send);

        if ($result['result'] !== true) {
            return false;
        }

        return $result;
    }

    /**
     * Function sends a verification email to the user (using $user->mail).
     */
    private function send_registration2015_mail($idstr = '')
    {
        $mailManager = \Drupal::service('plugin.manager.mail');

        $key = 'registration2015';
        $to = \Drupal::currentUser()->getEmail();
        $params = array();
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = true;

        $result = $mailManager->mail('smt_profile', $key, $to, $langcode, $params, null, $send);

        if ($result['result'] !== true) {
            return false;
        }

        return $result;

        // $message = t('An email notification has been sent to @email - @result.', array('@email' => $to, '@result' => print_r($result, true)));
        // drupal_set_message($message);
        // \Drupal::logger('d8mail')->notice($message);
    }


    public function payment_receipt($idstr) {

        if ($idstr == '') {
            drupal_set_message('Empty verification code. Cannot retrieve receipt. Error code EVVK10', 'error');
            return '';
        }

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        $sql = "SELECT s.*, d.amount as donation FROM smtmeeting2015 s
            LEFT JOIN smtdonations d ON s.idstr=d.idstr
            WHERE s.ID = ? AND s.idstr = ?";

        $data = $db->query($sql, array($uid, $idstr))->fetch();

        if (!$data) {
            drupal_set_message('Registration data could not be accessed! Error code EVVK11.', 'error');
            return '';
        }
        if ($data->status != 'confirmed') {
            drupal_set_message('Payment is not confirmed. Cannot proceed. Error code EVVK12.', 'error');
            return '';
        }

        $output = '<p>Receipt for SMT meeting 2015</p><table>';
        $output .= '<tr><td>Registration ID: </td><td>' . $data->num . '</td></tr>';
        $output .= '<tr><td>Created: </td><td>' . $data->created . '</td></tr>';
        $output .= '<tr><td>Verification code: </td><td>' . $data->idstr . '</td></tr>';
        $output .= '<tr><td>Options: </td><td>' . $data->options . '</td></tr>';
        $output .= '<tr><td>Amount: </td><td>' . $data->payment . '</td></tr>';
        $output .= '<tr><td>Conference guide: </td><td>' . $data->confguide . '</td></tr>';

        if ($data->donation > 0) {
            $output .= '<tr><td>Donation: </td><td>' . number_format($data->donation, 2) . 'USD' . '</td></tr>';
        }

        $output .= '</table>';

        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );
    }
}
