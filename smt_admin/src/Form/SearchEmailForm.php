<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Form\SearchEmailForm.
 */

namespace Drupal\smt_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_admin\MemberUtils;

/**
 * Implements an example form.
 */
class SearchEmailForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtadmin_search_email_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['search_email'] = array(
            '#type' => 'fieldset',
            '#title' => t('Search by email'),
         );
        $form['search_email']['email'] = array(
            '#type' => 'textfield',
            '#title' => t('Email Address'),
        );
        $form['search_email']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Search by email'),
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if (trim($form_state->getValue('email')) === '') {
            $form_state->setErrorByName('search_email', t('Please enter an email address.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $email = $form_state->getValue('email');
        $form_state->setRedirect('smt_admin.searchEmail', array('email' => $email));
    }

}
