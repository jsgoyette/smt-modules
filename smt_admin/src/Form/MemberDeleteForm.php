<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Form\MemberDeleteForm.
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
class MemberDeleteForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtadmin_member_delete_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

        if ($id == 1) {
            drupal_set_message('You cannot delete member with ID 1', 'error');
            return $this->redirect('smt_admin.memberList');
        }

        $member = MemberUtils::member($id);

        if (empty($member)) {
            drupal_set_message('No member with that ID', 'error');
            return $this->redirect('smt_admin.memberList');
        }

        $form['markup'] = array(
            '#markup' => t('Delete this member from the member database?')
        );

        $form['deletemember_uid'] = array(
          '#type' => 'textfield',
          '#title' => t('ID'),
          '#disabled' => 'disabled',
          '#default_value' => $member->ID,
        );
        $form['deletemember_name'] = array(
          '#type' => 'textfield',
          '#title' => t('Name'),
          '#disabled' => 'disabled',
          '#default_value' => $member->fname . ' ' . $member->lname,
        );
        $form['deletemember_email'] = array(
          '#type' => 'textfield',
          '#title' => t('E-mail'),
          '#disabled' => 'disabled',
          '#default_value' => $member->email,
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Delete Member')
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
        $uid = $form_state->getValue('deletemember_uid');

        $sql = "DELETE FROM members WHERE ID = ?";
        $db->query($sql, array($uid));

        $sql = "DELETE FROM user__roles WHERE entity_id = ?";
        $db->query($sql, array($uid));

        drupal_set_message('Member Deleted');
        $form_state->setRedirect('smt_admin.memberList');

    }

}
