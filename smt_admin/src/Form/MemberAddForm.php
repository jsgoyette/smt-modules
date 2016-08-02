<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Form\MemberAddForm.
 */

namespace Drupal\smt_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_admin\MemberUtils;
use Drupal\user\Entity\User;

/**
 * Implements an example form.
 */
class MemberAddForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtadmin_member_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

        $profile = MemberUtils::smtprofile($id);

        if (empty($profile)) {
            drupal_set_message('Profile not found - ID ' . $id, 'error');
            return $this->redirect('smt_admin.pendingList');
        }

        $db = Database::getConnection();

        if (($profile->fname == '') || ($profile->lname == '')){
            drupal_set_message('This person has not provided first/last name.', 'warning');
        }
        else {

            $sql = "SELECT ID FROM members m WHERE m.fname LIKE ? AND m.lname LIKE ?";
            $memberWithSameName = $db->query($sql, array($profile->fname, $profile->lname))->fetch();

            if (!empty($memberWithSameName)) {
                $url = URL::fromRoute('smt_admin.memberEdit', array('id' => $memberWithSameName->ID));
                $link = \Drupal::l('here', $url);

                $msg = 'A person named ' . $profile->fname . ' ' . $profile->lname
                    . ' already exists in the SMT member database. Click ' . $link . ' to view.';
                drupal_set_message($this->t($msg), 'warning');
            }

        }

        $form['markup'] = array(
            '#markup' => t('Add this member to the member database?')
        );

        $form['addmember_uid'] = array(
          '#type' => 'textfield',
          '#title' => t('ID'),
          '#disabled' => 'disabled',
          '#default_value' => $profile->ID,
        );
        $form['addmember_name'] = array(
          '#type' => 'textfield',
          '#title' => t('Name'),
          '#disabled' => 'disabled',
          '#default_value' => $profile->fname . ' ' . $profile->lname,
        );
        $form['addmember_email'] = array(
          '#type' => 'textfield',
          '#title' => t('E-mail'),
          '#disabled' => 'disabled',
          '#default_value' => $profile->email,
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Add Member')
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

        $id = $form_state->getValue('addmember_uid');
        $user = User::load($id);
        $profile = MemberUtils::smtprofile($id);

        $today = date('Y-m-d');

        if (empty($profile)) {
            drupal_set_message('Creating SMT database record failed', 'error');
            return false;
        }

        $copy = array(
            ':ID' => $id,
            ':fname' => $profile->fname,
            ':lname' => $profile->lname,
            ':title' => $profile->title,
            ':add1' => $profile->add1,
            ':add2' => $profile->add2,
            ':add3' => $profile->add3,
            ':city' => $profile->city,
            ':state' => $profile->state,
            ':zip' => $profile->zip,
            ':country' => $profile->country,
            ':dept' => $profile->dept,
            ':email' => $profile->mail,
            ':phone' => $profile->phone,
            ':items' => $profile->items,
            ':fname2' => $profile->fname2,
            ':lname2' => $profile->lname2,
            ':gender' => $profile->gender,
            ':ethnicity' => $profile->ethnicity,
            ':rank' => $profile->rank,
            ':smtdirectory' => $profile->smtdirectory,
            ':date_created' => $today,
        );

        $sql = "INSERT INTO members (ID, fname, lname, title, add1, add2, add3, city, state, zip, country, dept, email, phone, items, fname2, lname2, gender, ethnicity, rank, smtdirectory, date_created) VALUES (:ID, :fname, :lname, :title, :add1, :add2, :add3, :city, :state, :zip, :country, :dept, :email, :phone, :items, :fname2, :lname2, :gender, :ethnicity, :rank, :smtdirectory, :date_created)";

        $res = $db->query($sql, $copy);

        // add role
        $user->addRole('smt');
        $user->save();

        // delete the smtprofile record
        if ($res) {
            $db->query("DELETE FROM smtprofiles WHERE ID = ?", array($id));
        }

        drupal_set_message('User added to member database!');
        $form_state->setRedirect('smt_admin.memberEdit', array('id' => $id));
    }
}
