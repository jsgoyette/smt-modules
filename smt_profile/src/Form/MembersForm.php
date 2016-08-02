<?php

/**
 * @file
 * Contains \Drupal\smt_profile\Form\MembersForm.
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
class MembersForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtprofile_members_form';
    }

    // check that they are member or have profile with lname and fname
    private function checkProfileInfo() {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        if ($uid == 1) $uid++;

        // if user is in the member table, it is enough - we won't ask name
        $sql = "SELECT * FROM members m WHERE m.ID = :id";
        $member = $db->query($sql, array(':id' => $uid))->fetch();

        if ($member) {
            return true;
        }

        $sql = "SELECT * FROM smtprofiles p WHERE p.ID = :id";
        $profile = $db->query($sql, array(':id' => $uid))->fetch();

        if (!$profile || empty($profile->fname) || empty($profile->lname)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        if (! $this->checkProfileInfo()) {
            $url = Url::fromRoute('smt_profile.profile');
            drupal_set_message($this->t('Please fill in your ' . \Drupal::l('profile', $url)
                . ' before joining SMT or renewing your membership.'), 'warning');
            return '';
        }

        $output = '<p>' . MemberUtils::member_status() . '</p>';
        $output .= '<p>If it is time for you to renew your membership, you can do so by printing,
        filling out, and mailing <a href="/sites/default/files/Membership_form_2014.pdf">this form</a> or (preferably) by filling out the online form below.
        </p><p>If you rejoin by filling out the online form below, you need to make two
        choices here concerning shipping and membership type, then click on the "continue" button and you will either be
        asked for more information (if you chose a joint membership) or be taken to the Paypal
        site, where you DO NOT have to join PayPal to pay for your membership renewal.</p>';
        $output .= '<p>The first choice is for a Shipping Option, whether or not you wish to receive a print copy of <i>Music
        Theory Spectrum.</i></p>';
        $output .= '<p>The second choice is your Membership Type. <p>Please note that the student and retired categories
        are now separate. Also note that there are separate categories for overseas members (that
        is, for members who reside outside of North American and who belong to
        another professional music society).<p> If you select the joint membership category, after you click on "continue" below, you
        will be asked to enter the name and number of the joint member. Please note that the joint
        member must be registered at this web site, and that you can get the number of the joint member
        from their profile page. <b>For any questions, or if you experience any problems in trying to join,
        please contact Executive Director Victoria Long at <a href="mailto:vlong@uchicago.edu">vlong@uchicago.edu</a>.</b></p>';

        $form['smtprofile_header'] = array(
            '#markup' => $output
        );

        $shippingoptions = array(
            '' => t(''),
            'print' => t('Ship print copy'),
            'noprint' => t('Do not ship a print copy')
        );

        $membertypeoptions = array(
            '' => t(''),
            'regular' => t('SMT membership, regular (85.00)'),
            'student' => t('SMT membership, student (40.00)'),
            'subsidized' => t('SMT membership, subsidized (40.00)'),
            'retired' => t('SMT membership, retired (40.00)'),
            'overseas' => t('SMT membership, overseas and member of other professional music society (75.00)'),
            'joint' => t('Joint SMT membership, regular (95.00)'),
            'jointstudent' => t('Joint SMT membership, student (50.00)'),
            'jointretired' => t('Joint SMT membership, retired (50.00)'),
            'jointoverseas' => t('Joint SMT membership, overseas and member of other professional music society (85.00)')
        );

        $default_shipping = !empty($form_state->getValue('smtprofile_shipping')) ?
            $form_state->getValue('smtprofile_shipping') : '';
        $default_membertype = !empty($form_state->getValue('smtprofile_membertype')) ?
            $form_state->getValue('smtprofile_membertype') : '';

        $form['smtprofile_shippingoptions'] = array(
            '#type' => 'fieldset',
            '#title' => 'Shipping options',
        );

        $form['smtprofile_shippingoptions']['smtprofile_shipping'] = array(
            '#type' => 'select',
            '#title' => t('Shipping options'),
            '#default_value' => $default_shipping,
            '#options' => $shippingoptions,
        );

        $form['smtprofile_membershipoptions'] = array(
            '#type' => 'fieldset',
            '#title' => 'Membership options',
        );

        $form['smtprofile_membershipoptions']['smtprofile_membertype'] = array(
            '#type' => 'select',
            '#title' => t('Membership type'),
            '#default_value' => $default_membertype,
            '#options' => $membertypeoptions,
        );

        if ($default_membertype == 'joint' || $default_membertype == 'jointstudent'
            || $default_membertype == 'jointretired'  || $default_membertype == 'jointoverseas'
        ) {
            $form['smtprofile_is_joint'] = array(
                '#type' => 'hidden',
                '#value' => 'joint',
            );
            $form['smtprofile_jointoptions'] = array(
                '#type' => 'fieldset',
                '#title' => 'Information about joint member',
            );
            $form['smtprofile_jointoptions']['smtprofile_jointid'] = array(
                '#type' => 'textfield',
                '#title' => 'Joint member ID',
                '#default_value' => $form_state->getValue('smtprofile_jointid'),
            );
            $form['smtprofile_jointoptions']['smtprofile_jointfirst'] = array(
                '#type' => 'textfield',
                '#title' => 'Joint member first name',
                '#default_value' => $form_state->getValue('smtprofile_jointfirst'),
            );
            $form['smtprofile_jointoptions']['smtprofile_jointlast'] = array(
                '#type' => 'textfield',
                '#title' => 'Joint member last name',
                '#default_value' => $form_state->getValue('smtprofile_jointlast'),
            );
        }
        else {
            $form['smtprofile_is_joint'] = array(
                '#type' => 'hidden',
                '#value' => '',
            );
        }

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Continue')
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        $uid = \Drupal::currentUser()->id();

        // check first if the form needs to be rebuilt
        if ($form_state->getValue('smtprofile_membertype') == 'joint'
            || $form_state->getValue('smtprofile_membertype') == 'jointstudent'
            || $form_state->getValue('smtprofile_membertype') == 'jointretired'
            || $form_state->getValue('smtprofile_membertype') == 'jointoverseas'
        ) {
            if ($form_state->getValue('smtprofile_is_joint') == '') {
                $form_state->setValue('smtprofile_is_joint', 'joint');
                $form_state->setRebuild(true);
                return;
            }
        }
        else {
            if ($form_state->getValue('smtprofile_is_joint') == 'joint') {
                $form_state->setValue('smtprofile_is_joint', '');
                $form_state->setRebuild(true);
                return;
            }
        }


        if ($form_state->getValue('smtprofile_shipping') == '') {
            $form_state->setErrorByName('smtprofile_shipping', 'Please select shipping options.');
        }
        else if ($form_state->getValue('smtprofile_membertype') == '') {
            $form_state->setErrorByName('smtprofile_membertype', 'Please select membership options.');
        }
        else if ($form_state->getValue('smtprofile_is_joint') == 'joint') {

            if ($form_state->getValue('smtprofile_jointid') == '') {
                $form_state->setErrorByName('smtprofile_jointid', 'Please enter ID of joint member.');
            }
            else if (!is_numeric($form_state->getValue('smtprofile_jointid'))) {
                $form_state->setErrorByName('smtprofile_jointid', "'" . $form_state->getValue('smtprofile_jointid') . "' is not a valid ID");
            }
            else if ((int) $form_state->getValue('smtprofile_jointid') == (int) $uid) {
                $form_state->setErrorByName('smtprofile_jointid', 'Please enter ID of the joint member, not your own ID');
            }
            else if ($form_state->getValue('smtprofile_jointfirst') == '') {
                $form_state->setErrorByName('smtprofile_jointfirst', 'Please enter first name of joint member.');
            }
            else if ($form_state->getValue('smtprofile_jointlast') == '') {
                $form_state->setErrorByName('smtprofile_jointlast', 'Please enter last name of joint member.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $uid = \Drupal::currentUser()->id();

        $id = md5(uniqid());

        $joint = '';
        $jointid = '';
        $jointfname = '';
        $jointlname = '';

        if ($form_state->getValue('smtprofile_is_joint') === 'joint') {
            $joint = 'joint';
            $jointid = $form_state->getValue('smtprofile_jointid');
            $jointfname = $form_state->getValue('smtprofile_jointfirst');
            $jointlname = $form_state->getValue('smtprofile_jointlast');
        }

        $db = Database::getConnection();

        $sql = "INSERT INTO smtpayment (id, uid, shipping, type, joint, jointid, jointfname, jointlname) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $res = $db->query($sql, array(
            $id, $uid,
            $form_state->getValue('smtprofile_shipping'),
            $form_state->getValue('smtprofile_membertype'),
            $joint, $jointid, $jointfname, $jointlname
        ));

        $form_state->setRedirect('smt_profile.paypal', array('id' => $id));

    }

}
