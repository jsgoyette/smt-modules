<?php

/**
 * @file
 * Contains \Drupal\smt_profile\Form\NominationsForm.
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
class NominationsForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtprofile_nominations_form';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        if (!MemberUtils::is_active_member()) {
            drupal_set_message('Only active SMT members are allowed to nominate! Please update your membership.', 'error');
            return '';
        }

        $output = '<p>Nominate President-Elect and <i>two</i> Members-at-Large.</p>';

        $form['smtprofile_header'] = array(
            '#markup' => $output
        );

        $form['smtnomination2016_president_elect'] = array(
            '#type' => 'textfield',
            '#title' => t('Nominate one President-Elect'),
            '#prefix' => '<hr>',
        );
        $form['smtnomination2016_members'] = array(
            '#type' => 'textfield',
            '#title' => t('Nominate first Member-at-Large'),
        );
        $form['smtnomination2016_members2'] = array(
            '#type' => 'textfield',
            '#title' => t('Nominate second Member-at-Large'),
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit')
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

        $vice_value = $form_state->getValue('smtnomination2016_president_elect');
        $members_value = $form_state->getValue('smtnomination2016_members');
        $members2_value = $form_state->getValue('smtnomination2016_members2');

        $sql = "INSERT INTO smtnominations2016 (ID, president_elect, members, members2) VALUES (?, ?, ?, ?)";
        $success = $db->query($sql, array($uid, $vice_value, $members_value, $members2_value));

        if (!$success) {
            drupal_set_message($this->t('Nomination failed. Please contact site administration!'), 'error');
        }
        else {
            drupal_set_message($this->t('<p><b>Thank your for your participation!</b></p><p>Your nominations have been succesfully recorded.</p>'));
            $form_state->setRedirect('smt_profile.welcome');
        }

    }

}
