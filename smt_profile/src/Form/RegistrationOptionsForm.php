<?php

/**
 * @file
 * Contains \Drupal\smt_profile\Form\RegistrationOptionsForm.
 */

namespace Drupal\smt_profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_profile\MemberUtils;

/**
 * Implements an example form.
 */
class RegistrationOptionsForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtprofile_registrationoptions_form';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        if (!MemberUtils::is_active_member()) {
            drupal_set_message('Only active SMT members are allowed to register! Please update your membership.', 'error');
            return '';
        }

        $sql = "SELECT * FROM smtdonations d
            WHERE d.userid = ? AND d.status='pledged'
            AND YEAR(d.pledge_date)=2015
            ORDER BY d.pledge_date ASC";

        $pledged_donation = $db->query($sql, array($uid))->fetch();

        $registrationoptions = array(
            '' => t(''),
            'SMT member, regular' => t('SMT member, individual'),
            'SMT member, student' => t('SMT member, student'),
            'SMT member, retired' => t('SMT member, retired')
        );

        $guideoptions = array(
            '' => '',
            'Be guide' => t('Yes, I would like to be a Conference Guide'),
            'Be assisted by guide' => t('I would like to be assisted by a Conference Guide')
        );

        $feetext = '<table border="0"><tr><td>﻿Individual member:</td><td>received <b>on or before September 28</b></td><td>$110 USD</td></tr>';
        $feetext .= '<tr><td>﻿Individual member:</td><td>received <b>between September 29 and October 19</b></td><td>$155 USD</td></tr>';
        $feetext .= '<tr><td>Student/retired:</td><td>received <b>on or before September 28</b></td><td>$55 USD</td></tr>';
        $feetext .= '<tr><td>Student/retired:</td><td>received <b>between September 29 and October 19</b></td><td>$80 USD</td></tr></table>';

        $form['registration'] = array(
            '#type' => 'fieldset',
            '#title' => 'Registration options',
            '#description' => $feetext,
        );
        $form['registration']['registration_options'] = array(
            '#type' => 'select',
            '#title' => t('Membership Type'),
            '#default_value' => '',
            '#options' => $registrationoptions,
        );
        $form['registration']['confguide_options'] = array(
            '#type' => 'select',
            '#title' => t('Conference guide program'),
            '#default_value' => '',
            '#options' => $guideoptions,
        );

        $donationdescription = '<p>A $50 donation to SMT entitles the contributor to one complimentary ';
        $donationdescription .= 'beverage ticket for the opening reception. A donation of $100 or more ';
        $donationdescription .= 'entitles the contributor to two complimentary beverage tickets.</p>';

        if (!empty($pledged_donation)) {

            $pledgedescription .= '<p>You have pledged a donation for this year for the amount of $'.$pledged_donation->amount.'. ';
            $pledgedescription .= 'Would like to fulfill your pledge now? </p>';

            $form['donationfieldset'] = array(
                '#type' => 'fieldset',
                '#title' => 'Donation to SMT',
                '#description' => $pledgedescription,
            );
            $form['donationfieldset']['donation_fulfill_pledge'] = array(
                '#type' => 'checkbox',
                '#title' => t('Fulfill Pledged Donation'),
                '#default_value' => 1,
            );
            $form['donationfieldset']['donation'] = array(
                '#type' => 'textfield',
                '#title' => t('Donation'),
                '#default_value' => $pledged_donation->amount,
                '#disabled' => true
            );
            $form['donationfieldset']['donation_info'] = array(
                '#type' => 'markup',
                '#value' => $donationdescription,
            );
            $form['donationfieldset']['donation_idstr'] = array(
                '#type' => 'hidden',
                '#default_value' => $pledged_donation->idstr,
            );
        } else {
            $form['donationfieldset'] = array(
                '#type' => 'fieldset',
                '#title' => 'Donation to SMT',
            );
            $form['donationfieldset']['donation'] = array(
                '#type' => 'textfield',
                '#title' => t('Donation'),
                '#default_value' => ''
            );
            $form['donationfieldset']['donation_info'] = array(
                '#type' => 'markup',
                '#value' => $donationdescription,
            );
        }

        $acc = 'Please direct any request for personal accommodations (e.g., wheelchair ';
        $acc .= 'access) to SMT\'s Executive Director, Victoria Long ';
        $acc .= '(<a href="mailto:vlong@uchicago.edu">vlong@uchicago.edu</a>). ';
        $acc .= 'Please make your request as far in advance as possible to allow us ';
        $acc .= 'time to make necessary arrangements.';

        $form['accessibilityfieldset'] = array(
            '#type' => 'fieldset',
            '#title' => 'Accessibility Requests',
            '#description' => $acc,
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Proceed to payment'),
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        if ($form_state->getValue('registration_options') == '') {
            $form_state->setErrorByName('registration_options', 'Please select registration options');
        }
        if ($form_state->getValue('donation') != '') {
            if (!(is_numeric($form_state->getValue('donation')))) {
                $form_state->setErrorByName('donation', 'Donation amount not numeric.');
            }
            $donationfloat = (float) $form_state->getValue('donation');
            if ($donationfloat <= 0) {
                $form_state->setErrorByName('donation', 'Please check donation amount.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();
        $member = MemberUtils::member();

        $idstr = md5(uniqid());
        $created = date('Y-m-d H:i:s');
        $options = $form_state->getValue('registration_options');
        $confguide = $form_state->getValue('confguide_options');
        $payment = 0;

        $now = new \DateTime('now', new \DateTimeZone('America/New_York'));
        $now = $now->format('Y-m-d H:i:s');

        if ($now < '2016-09-29 00:00:00') {
            if ($options == 'SMT member, regular') {
                $payment = 110;
            } else if ($options == 'SMT member, student') {
                $payment = 55;
            } else if ($options == 'SMT member, retired') {
                $payment = 55;
            } else if ($options == 'Lifetime SMT member') {
                $payment = 0;
            } else {
                drupal_set_message('Unknown registration option', 'error');
                return;
            }
        } else if ($now < '2016-10-20 00:00:00') {
            if ($options == 'SMT member, regular') {
                $payment = 155;
            } else if ($options == 'SMT member, student') {
                $payment = 80;
            } else if ($options == 'SMT member, retired') {
                $payment = 80;
            } else if ($options == 'Lifetime SMT member') {
                $payment = 0;
            } else {
                drupal_set_message('Unknown registration option', 'error');
                return;
            }
        } else {
            drupal_set_message('Online registration is closed', 'error');
            return;
        }

        $donation = 0;
        $fulfilling_pledge = false;

        if (!empty($form_state->getValue('donation_idstr')) && !empty($form_state->getValue('donation_fulfill_pledge'))) {
            $donation = (float) $form_state->getValue('donation');
            $fulfilling_pledge = true;
        } else if ($form_state->getValue('donation') != '' && is_numeric($form_state->getValue('donation'))) {
            if ($form_state->getValue('donation') > 0) {
                $donation = (float) $form_state->getValue('donation');
            }
        }

        // soft delete previous attempts
        $sql = "UPDATE smtmeeting2015 SET deleted = 1 WHERE ID = ? AND status = 'new'";
        $db->query($sql, array($uid));

        $sql = "INSERT INTO smtmeeting2015 (idstr, ID, created, options, confguide, payment, status) VALUES (?, ?, ?, ?, ?, ?, 'new')";
        $success = $db->query($sql, array($idstr, $uid, $created, $options, $confguide, $payment));

        if ($fulfilling_pledge) {

            $sql = "UPDATE smtdonations d SET d.idstr = ? WHERE d.idstr= ? AND d.userid = ?";
            $success = $db->query($sql, array($idstr, $form_state->getValue('donation_idstr'), $uid));

        } else if (!empty($donation)) {

            $now = date('Y-m-d H:i:s');
            $sql = "INSERT INTO smtdonations (idstr, created, userid, lastname, firstname, email, address1, address2, city, zip, state, country, member, amount, status, info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', '')";

            $success = $db->query($sql, array(
                $idstr, $now, $uid, $member->lname, $member->fname, $member->email,
                $member->add1, $member->add2, $member->city, $member->zip,
                $member->state, $member->country, 1, $donation
            ));
        }

        $form_state->setRedirect('smt_profile.conference_registration_payment', array(
            'idstr' => $idstr
        ));
    }

}
