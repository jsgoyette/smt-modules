<?php

/**
 * @file
 * Contains \Drupal\smt_profile\Form\RegistrationInfoForm
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
class RegistrationInfoForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtprofile_registrationinfo_form';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();
        $user = \Drupal\user\Entity\User::load($uid);
        $member = MemberUtils::is_active_member();

        if (!$member) {
            drupal_set_message('Only active SMT members are allowed to register! Please update your membership.', 'error');
            return '';
        }

        $form['smtprofile'] = array(
            '#type' => 'fieldset',
            '#title' => 'Registrant information',
            '#description' => 'Please check that your personal information is up-to-date, and please note that your name and affiliation as given here will be used on your conference badge. When you have finished, go to bottom of this page and click on the button to  proceed to conference options.',
        );

        $titleoptions = array(
            '' => t(''), 'Dr.' => t('Dr.'),
            'Miss' => t('Miss'), 'Mr.' => t('Mr.'),
            'Mrs.' => t('Mrs.'), 'Ms.' => t('Ms.'),'Prof.' => t ('Prof.')
        );

        $form['smtprofile']['smtprofile_ID'] = array(
            '#type' => 'textfield',
            '#title' => 'SMT ID',
            '#value' => $uid,
            '#disabled' => 'disabled',
        );
        $form['smtprofile']['smtprofile_fname'] = array(
            '#type' => 'textfield',
            '#title' => t('First name'),
            '#default_value' => $member->fname,
        );
        $form['smtprofile']['smtprofile_lname'] = array(
            '#type' => 'textfield',
            '#title' => t('Last name'),
            '#default_value' => $member->lname,
        );
        $form['smtprofile']['smtprofile_title'] = array(
            '#type' => 'select',
            '#title' => t('Title'),
            '#default_value' => '',
            '#options' => $titleoptions,
            '#default_value' => $member->title,
        );
        $form['smtprofile']['smtprofile_dept'] = array(
            '#type' => 'textfield',
            '#title' => t('Affiliation'),
            '#default_value' => $member->dept,
        );
        $form['smtprofile']['smtprofile_add1'] = array(
            '#type' => 'textfield',
            '#title' => t('Address 1'),
            '#default_value' => $member->add1,
        );
         $form['smtprofile']['smtprofile_add2'] = array(
            '#type' => 'textfield',
            '#title' => t('Address 2'),
            '#default_value' => $member->add2,
        );

        $form['smtprofile']['smtprofile_city'] = array(
            '#type' => 'textfield',
            '#title' => t('City'),
            '#default_value' => $member->city,
        );
        $form['smtprofile']['smtprofile_state'] = array(
            '#type' => 'textfield',
            '#title' => t('State'),
            '#default_value' => $member->state,
        );
        $form['smtprofile']['smtprofile_country'] = array(
            '#type' => 'textfield',
            '#title' => t('Country'),
            '#default_value' => $member->country,
        );
        $form['smtprofile']['smtprofile_zip'] = array(
            '#type' => 'textfield',
            '#title' => t('Zip'),
            '#default_value' => $member->zip,
        );
        $form['smtprofile']['smtprofile_phone'] = array(
            '#type' => 'textfield',
            '#title' => t('Phone'),
            '#default_value' => $member->phone,
        );
        $form['smtprofile']['smtprofile_email'] = array(
            '#type' => 'textfield',
            '#title' => t('E-mail'),
            '#default_value' => $user->get('mail')->value,
            '#disabled' => 'disabled',
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Proceed to conference options'),
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {


    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        $sql = "UPDATE members SET fname = ?, lname = ?, title = ?, dept = ?,
            add1 = ?, add2 = ?, city = ?, state = ?, country = ?, zip = ?,
            phone = ? WHERE ID = ?";

        $success = $db->query($sql, array(
            $form_state->getValue('smtprofile_fname'), $form_state->getValue('smtprofile_lname'),
            $form_state->getValue('smtprofile_title'), $form_state->getValue('smtprofile_dept'),
            $form_state->getValue('smtprofile_add1'), $form_state->getValue('smtprofile_add2'),
            $form_state->getValue('smtprofile_city'), $form_state->getValue('smtprofile_state'),
            $form_state->getValue('smtprofile_country'), $form_state->getValue('smtprofile_zip'),
            $form_state->getValue('smtprofile_phone'), $uid
        ));

        $form_state->setRedirect('smt_profile.conference_registration_options');

    }

}
