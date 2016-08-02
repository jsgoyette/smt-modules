<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Form\SearchIDForm.
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
class SearchIDForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtadmin_search_id_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

        $form['search_id'] = array(
            '#type' => 'fieldset',
            '#title' => t('Search by ID'),
        );
        $form['search_id']['smt_id'] = array(
            '#type' => 'textfield',
            '#title' => t('ID'),
        );
        $form['search_id']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Search by ID'),
            '#default' => $id
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if (!is_numeric($form_state->getValue('smt_id'))) {
            $form_state->setErrorByName('search_id', t('Please enter a number.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $id = $form_state->getValue('smt_id');
        $member = MemberUtils::member($id);

        if ($member) {
            $form_state->setRedirect('smt_admin.memberEdit', array('id' => $id));
        }
        else {
            drupal_set_message('ID ' . $id . ' not found.', 'warning');
        }

    }

}
