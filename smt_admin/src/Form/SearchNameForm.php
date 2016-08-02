<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Form\SearchNameForm.
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
class SearchNameForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtadmin_search_name_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['search_name'] = array(
            '#type' => 'fieldset',
            '#title' => t('Search by name'),
         );
        $form['search_name']['fname'] = array(
            '#type' => 'textfield',
            '#title' => t('First name'),
        );
        $form['search_name']['lname'] = array(
            '#type' => 'textfield',
            '#title' => t('Last name'),
        );
        $form['search_name']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Search by name'),
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if ((trim($form_state->getValue('fname')) === '') && (trim($form_state->getValue('lname')) === '')) {
            $form_state->setErrorByName('search_name', t('Please enter first name and/or last name.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $fname_checked = trim($form_state->getValue('fname'));
        if ($fname_checked === '') {
            $fname_checked = '-';
        }

        $lname_checked = trim($form_state->getValue('lname'));
        if ($lname_checked === '') {
            $lname_checked = '-';
        }

        $form_state->setRedirect('smt_admin.searchName', array('fname' => $fname_checked, 'lname' => $lname_checked));

    }

}
