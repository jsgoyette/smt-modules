<?php

/**
 * @file
 * Contains \Drupal\smt_donations\Form\DonationsForm.
 */

namespace Drupal\smt_donations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_profile\MemberUtils;

/**
 * Implements an example form.
 */
class DonationsForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtdonations_donations_form';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();
        $user = \Drupal\user\Entity\User::load($uid);

        $member = MemberUtils::member();

        if ($member) {
            $lastname_default = $member->lname;
            $firstname_default = $member->fname;
            $address1_default = $member->add1;
            $address2_default = $member->add2;
            $city_default = $member->city;
            $state_default = $member->state;
            $zip_default = $member->zip;
            $country_default = $member->country;
        }

        if ($uid) {
            $member_default = array('smtmember');
            $email_default = $user->get('mail')->value;
        }
        else {
            $member_default = array('');
        }

        $contribution_type = !empty($form_state->getValue('contribution_type')) ? $form_state->getValue('contribution_type') : '';

        if ($contribution_type == 2) {

            $sql = "SELECT idstr, amount, pledge_date, pledge_id FROM smtdonations
                WHERE userid = ? AND status='pledged' AND deleted=0 AND YEAR(pledge_date) >= YEAR(NOW())
                GROUP BY pledge_id, idstr, amount, pledge_date ORDER BY pledge_date";

            $rows = $db->query($sql, array($uid))->fetchAll();
            $pledges = array();

            foreach ($rows as $row) {
                $pledges[$row->idstr] = '$' . $row->amount . ' - Pledged for year ' . date('Y', strtotime($row->pledge_date));
            }

            $form['smtdonations_fs_pledge']['contribution_type'] = array(
                '#type' => 'hidden',
                '#value' => 2,
            );
            $form['smtdonations_fs_pledge_select'] = array(
                '#type' => 'fieldset',
                '#description' => '<p><b>Select from the pledged donations below.</b></p>',
            );
            if (empty($pledges)) {
                $form['smtdonations_fs_pledge_select']['empty_pledges'] = array(
                    '#value' => '<p><i>You do not have any pledges.</i></p><p><a href="/smtdonations">Back to form</a></p>'
                );
            } else {
                $form['smtdonations_fs_pledge_select']['selected_pledge'] = array(
                    '#type' => 'radios',
                    '#title' => t(''),
                    '#options' => $pledges,
                    '#default_value' => 0,
                );
                $form['smtdonations_fs_pledge_select']['back'] = array(
                    '#value' => '<p><a href="/smtdonations">Back to form</a></p>'
                );
            }
        } else {

            $form['smtdonations_fs_pledge'] = array(
                '#type' => 'fieldset',
                // '#description' => '<b>YES</b>, I’d like to support the SMT 40 campaign with my pledge of this amount below for the next five years (2013-2017).',
            );
            $form['smtdonations_fs_pledge']['contribution_type'] = array(
                '#type' => 'radios',
                '#title' => t(''),
                '#options' => array(
                    t('<b>YES</b>, I’d like to support the SMT 40 campaign with my pledge of the amount below for each of the next four years (2014-2017).'),
                    t('I\'d prefer to make a one-year pledge'),
                    t('I want to fulfill a previous pledge (log in and press continue below to see your pledges)'),
                ),
                '#default_value' => !empty($contribution_type) ? $contribution_type : 0,
            );

            $form['smtdonations_fs_amount'] = array(
                '#type' => 'fieldset',
                '#description' => 'Amount of contribution',
            );
            $form['smtdonations_fs_amount']['smtdonations_amount'] = array(
                '#type' => 'radios',
                '#title' => t('Select an Amount'),
                '#options' => array(
                    '25' => t('$25'),
                    '50' => t('$50'),
                    '100' => t('$100'),
                    '250' => t('$250'),
                    '500' => t('$500'),
                    '1000' => t('$1,000'),
                    'Other' => t('Other'),
                ),
            );
            $form['smtdonations_fs_amount']['smtdonations_other_amount'] = array(
                '#type' => 'textfield',
                '#title' => 'Other Amount',
                '#size' => '10',
            );

            $form['smtdonations_fs_memorial'] = array(
                '#type' => 'fieldset',
                '#description' => 'You may choose to make your gift in honor of or in memory of someone',
            );
            $form['smtdonations_fs_memorial']['smtdonations_honor'] = array(
                '#type' => 'textfield',
                '#title' => 'In honor of',
            );
            $form['smtdonations_fs_memorial']['smtdonations_memory'] = array(
                '#type' => 'textfield',
                '#title' => 'In memory of',
            );

            $form['smtdonations_fs_reason'] = array(
                '#type' => 'fieldset',
            );
            $form['smtdonations_fs_reason']['smtdonations_reason_text'] = array(
                '#type' => 'textarea',
                '#title' => 'Reason for Donation (optional)',
            );
            $form['smtdonations_fs_donor'] = array(
                '#type' => 'fieldset',
                '#description' => 'Please let us know your name, email and mailing address so that we can send you an electronic acknowledgement or paper receipt:',
            );
            $form['smtdonations_fs_donor']['smtdonations_lastname'] = array(
                '#type' => 'textfield',
                '#title' => 'Last name',
                '#default_value' => $lastname_default,
            );
            $form['smtdonations_fs_donor']['smtdonations_firstname'] = array(
                '#type' => 'textfield',
                '#title' => 'First name',
                '#default_value' => $firstname_default,
            );
            $form['smtdonations_fs_donor']['smtdonations_email'] = array(
                '#type' => 'textfield',
                '#title' => 'Email',
                '#default_value' => $email_default,
            );
            $form['smtdonations_fs_donor']['smtdonations_address1'] = array(
                '#type' => 'textfield',
                '#title' => 'Address line 1',
                '#default_value' => $address1_default,
            );
            $form['smtdonations_fs_donor']['smtdonations_address2'] = array(
                '#type' => 'textfield',
                '#title' => 'Address line 2',
                '#default_value' => $address2_default,
            );
            $form['smtdonations_fs_donor']['smtdonations_city'] = array(
                '#type' => 'textfield',
                '#title' => 'City',
                '#default_value' => $city_default,
            );
            $form['smtdonations_fs_donor']['smtdonations_state'] = array(
                '#type' => 'textfield',
                '#title' => 'State',
                '#default_value' => $state_default,
            );
            $form['smtdonations_fs_donor']['smtdonations_zip'] = array(
                '#type' => 'textfield',
                '#title' => 'Zip',
                '#size' => '10',
                '#default_value' => $zip_default,
            );
            $form['smtdonations_fs_donor']['smtdonations_country'] = array(
                '#type' => 'textfield',
                '#title' => 'Country',
                '#default_value' => $country_default,
            );
            $form['smtdonations_fs_donor']['smtdonations_member'] = array(
                '#title' => 'SMT member',
                '#type' => 'checkboxes',
                '#options' => array('smtmember' => t('SMT member')),
                '#default_value' => $member_default,
            );
        }

        $form['my_captcha_element'] = array(
             '#type' => 'captcha',
             '#description' => 'This question is for testing whether you are a human visitor and to prevent automated spam submissions.',
             '#captcha_type' => 'smtcaptcha/SMT - sequence arithmetic',
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Continue'),
        );

        return $form;

    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        // don't validate fields if going to fulfill pledge
        if ($form_state->getValue('contribution_type') == '2' && empty($uid)) {
            $form_state->setErrorByName('contribution_type', 'Please log in to select pledged donation');
            return;
        } else if ($form_state->getValue('contribution_type') == '2' && empty($form_state->getValue('selected_pledge'))) {
            $form_state->setRebuild(true);
            return;
        } else if ($form_state->getValue('contribution_type') == '') {
            $form_state->setErrorByName('contribution_type', 'Please choose what to do');
            return;
        }

        if ($form_state->getValue('contribution_type') != '2') {
            if ($form_state->getValue('smtdonations_amount') == '' && $form_state->getValue('smtdonations_other_amount') == '') {
                $form_state->setErrorByName('smtdonations_amount', 'Please provide an amount');
                return;
            } else if ($form_state->getValue('smtdonations_amount') == '' || $form_state->getValue('smtdonations_amount') == 'Other') {
                $donationfloat = (float) $form_state->getValue('smtdonations_other_amount');
                if ($donationfloat <= 0.01 || !(is_numeric($form_state->getValue('smtdonations_other_amount')))) {
                    $form_state->setErrorByName('smtdonations_other_amount', 'Please provide a valid amount');
                    return;
                }
            }
            if ($form_state->getValue('smtdonations_lastname') == '') {
                $form_state->setErrorByName('smtdonations_lastname', 'Please enter last name');
            }
            else if ($form_state->getValue('smtdonations_firstname') == '') {
                $form_state->setErrorByName('smtdonations_firstname', 'Please enter first name');
            }
            else if ($form_state->getValue('smtdonations_email') == '') {
                $form_state->setErrorByName('smtdonations_email', 'Please enter email');
            }
            else if (!valid_email_address($form_state->getValue('smtdonations_email'))) {
                $form_state->setErrorByName('smtdonations_email', 'Please check email address');
            }
            else if ($form_state->getValue('smtdonations_address1') == '') {
                $form_state->setErrorByName('smtdonations_address1', 'Please enter address');
            }
            else if ($form_state->getValue('smtdonations_city') == '') {
                $form_state->setErrorByName('smtdonations_city', 'Please enter address');
            }
            else if ($form_state->getValue('smtdonations_zip') == '') {
                $form_state->setErrorByName('smtdonations_zip', 'Please enter address');
            }
            else if ($form_state->getValue('smtdonations_country') == '') {
                $form_state->setErrorByName('smtdonations_country', 'Please enter address');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        if (!empty($form_state->getValue('selected_pledge'))) {

            $form_state->setRedirect('smt_donations.paypal', array(
                'id' => $form_state->getValue('selected_pledge')
            ));

        } else {

            $isMember = 0;
            if (in_array('smtmember', array_filter($form_state->getValue('smtdonations_member')))) {
                $isMember = 1;
            }

            $donation = ($form_state->getValue('smtdonations_amount') == '' || $form_state->getValue('smtdonations_amount') == 'Other') ?
                $form_state->getValue('smtdonations_other_amount') : $form_state->getValue('smtdonations_amount');
            $donation = (float) $donation;

            if ($form_state->getValue('contribution_type') == 2) {
                // goto page where they can select amount

            } else if ($form_state->getValue('contribution_type') == 0) {

                $pledgedate = date('Y-m-d');
                $sql = "INSERT INTO smtpledges (uid, name, total_amount, date_pledged, notes) VALUES (?, ?, ?, ?, null)";

                $pledge_id = $db->query($sql, array($uid,
                    $form_state->getValue('smtdonations_firstname') . ' ' . $form_state->getValue('smtdonations_lastname'),
                    $donation, $pledgedate
                ), array('return' => Database::RETURN_INSERT_ID));

                $idstr = md5(uniqid());

                $mysqldate = date('Y-m-d H:i:s');
                $sql = "INSERT INTO smtdonations (idstr, created, userid, lastname, firstname, email, address1, address2, city, zip, state, country, reason, honor, memory, member, amount, status, pledge_id, pledge_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, ?)";
                $success = $db->query($sql, array($idstr, $mysqldate, $uid,
                    $form_state->getValue('smtdonations_lastname'),
                    $form_state->getValue('smtdonations_firstname'),
                    $form_state->getValue('smtdonations_email'),
                    $form_state->getValue('smtdonations_address1'),
                    $form_state->getValue('smtdonations_address2'),
                    $form_state->getValue('smtdonations_city'),
                    $form_state->getValue('smtdonations_zip'),
                    $form_state->getValue('smtdonations_state'),
                    $form_state->getValue('smtdonations_country'),
                    $form_state->getValue('smtdonations_reason_text'),
                    $form_state->getValue('smtdonations_honor'),
                    $form_state->getValue('smtdonations_memory'),
                    $isMember, $donation, $pledge_id, $pledgedate
                ));

                $form_state->setRedirect('smt_donations.paypal', array('id' => $idstr));

            } else {

                $idstr = md5(uniqid());
                $mysqldate = date('Y-m-d H:i:s');
                $sql = "INSERT INTO smtdonations (idstr, created, userid, lastname, firstname, email, address1, address2, city, zip, state, country, reason, honor, memory, member, amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')";
                    $success = $db->query($sql, array($idstr, $mysqldate, $uid, $form_state->getValue('smtdonations_lastname'),
                    $form_state->getValue('smtdonations_firstname'),
                    $form_state->getValue('smtdonations_email'),
                    $form_state->getValue('smtdonations_address1'),
                    $form_state->getValue('smtdonations_address2'),
                    $form_state->getValue('smtdonations_city'),
                    $form_state->getValue('smtdonations_zip'),
                    $form_state->getValue('smtdonations_state'),
                    $form_state->getValue('smtdonations_country'),
                    $form_state->getValue('smtdonations_reason_text'),
                    $form_state->getValue('smtdonations_honor'),
                    $form_state->getValue('smtdonations_memory'),
                    $isMember, $donation
                ));

                $form_state->setRedirect('smt_donations.paypal', array('id' => $idstr));
            }
        }
    }

}
