<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Form\MemberEditForm.
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
class MemberEditForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtadmin_member_edit_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

        $member = MemberUtils::member($id);

        if (empty($member)) {
            drupal_set_message('Member not found - ID ' . $id, 'error');
            return $this->redirect('smt_admin.memberList');
        }

        $titleoptions = array(
            '' => $this->t(''),
            'Dr.' => $this->t('Dr.'),
            'Miss' => $this->t('Miss'),
            'Mr.' => $this->t('Mr.'),
            'Mrs.' => $this->t('Mrs.'),
            'Ms.' => $this->t('Ms.'),
            'Prof.' => t ('Prof.')
        );

        $membertypeoptions = array(
            '' => $this->t(''),
            'SMT membership, regular' => $this->t('SMT membership, regular'),
            'SMT membership, student' => $this->t('SMT membership, student'),
            'SMT membership, subsidized' => $this->t('SMT membership, subsidized'),
            'SMT membership, retired' => $this->t('SMT membership, retired'),
            'SMT membership, overseas' => $this->t('SMT membership, overseas and member of other professional music society'),
            'Joint SMT membership, regular' => $this->t('Joint SMT membership, regular'),
            'Joint SMT membership, student' => $this->t('Joint SMT membership, student'),
            'Joint SMT membership, retired' => $this->t('Joint SMT membership, retired'),
            'Joint SMT membership, overseas' => $this->t('Joint SMT membership, overseas and member of other professional music society'),
            'Lifetime SMT membership' => $this->t('Lifetime SMT membership')
        );

        $rank_options = array(
            '' => '',
            'prof' => $this->t('Professor'),
            'assist_prof' => $this->t('Assistant Professor'),
            'assoc_prof' => $this->t('Associate Professor'),
            'grad' => $this->t('Graduate Student'),
            'undergrad' => $this->t('Undergraduate'),
            'retired' => $this->t('Retired'),
            'limited_year' => $this->t('Limited term, 1-year position'),
            'limited_continuing' => $this->t('Limited term, continuing appointment'),
            'other' => $this->t('Other'),
        );

        $form['smtprofile_ID'] = array(
            '#type' => 'hidden',
            '#title' => 'smt_id',
            '#value' => $id,
        );
        $form['smtprofile_title'] = array(
            '#type' => 'select',
            '#title' => $this->t('Title'),
            '#default_value' => '',
            '#options' => $titleoptions,
            '#default_value' => $member->title,
        );
        $form['smtprofile_fname'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('First name'),
            '#default_value' => $member->fname,
        );
        $form['smtprofile_lname'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Last name'),
            '#default_value' => $member->lname,
        );
        $form['smtprofile_add1'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address 1'),
            '#default_value' => $member->add1,
            '#prefix' => '<hr>',
        );
        $form['smtprofile_add2'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address 2 (if needed)'),
            '#default_value' => $member->add2,
        );
        $form['smtprofile_add3'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address 3 (if needed)'),
            '#default_value' =>  $member->add3,
        );
        $form['smtprofile_city'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('City'),
            '#default_value' => $member->city,
        );
        $form['smtprofile_state'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('State/Province'),
            '#default_value' => $member->state,
        );
        $form['smtprofile_zip'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('ZIP/Postal Code'),
            '#default_value' => $member->zip,
        );
        $form['smtprofile_country'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Country'),
            '#default_value' => $member->country,
        );

        $email_link = \Drupal::l('here', URL::fromRoute('entity.user.edit_form', array('user'=>$id)));

        $form['smtprofile_email'] = array(
            '#type' => 'textfield',
            '#title' => $this->t("E-mail (change email $email_link)"),
            '#disabled' => 'disabled',
            '#default_value' => $member->email,
        );
        $form['smtprofile_phone'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Phone'),
            '#default_value' => $member->phone,
        );
        $form['smtprofile_employment'] = array(
            '#type' => 'select',
            '#title' => $this->t('Employment'),
            '#default_value' => '',
            '#options' => array('' => '', 'FT' => 'Full Time', 'PT' => 'Part Time', 'Unemployed' => 'Unemployed', 'Other' => 'Other'),
            '#default_value' => $member->employment,
            '#prefix' => '<hr>',
        );
        $form['smtprofile_dept'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Affiliation'),
            '#default_value' => $member->dept,
        );
        $form['smtprofile_rank'] = array(
            '#type' => 'select',
            '#title' => $this->t('Rank'),
            '#default_value' => '',
            '#options' => $rank_options,
            '#default_value' => $member->rank,
        );
        $form['smtprofile_items'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Member type'),
            '#disabled' => 'disabled',
            '#default_value' => $member->items,
        );
        $form['smtprofile_fname2'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Joint Member First name'),
            '#default_value' => $member->fname2,
        );
        $form['smtprofile_lname2'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Joint Member Last name'),
            '#default_value' => $member->lname2,
        );

        $gender_options = array(
            'Woman' => $this->t('Woman'),
            'Man' => $this->t('Man'),
            'Trans' => $this->t('Trans/Transgender'),
            'Another' => $this->t('Another identity'),
            'Prefer not to answer' => $this->t('Prefer not to answer')
        );

        $form['smtprofile_gender'] = array(
            '#type' => 'select',
            '#multiple' => true,
            '#title' => $this->t('Gender'),
            '#default_value' => '',
            '#options' => $gender_options,
            '#default_value' => explode(',', $member->gender),
            '#prefix' => '<hr>',
        );
        $form['smtprofile_gender_explain'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Please specify:'),
            '#default_value' => $member->gender_explain,
        );

        $ethnicty_options = array(
            'White' => $this->t('White'),
            'Black' => $this->t('Black'),
            'Hispan' => $this->t('Hispanic'),
            'AsianPac' => $this->t('Asian / Pacific islander'),
            'NatAm' => $this->t('Native American'),
            'FirstNation' => $this->t('First Nation'),
            'Mixed' => $this->t('Mixed Race'),
            'Unknown' => $this->t('Race / Ethnicity unknown'),
            'Prefer not to answer' => $this->t('Prefer not to answer'),
        );

        $form['smtprofile_ethnicity'] = array(
            '#type' => 'select',
            '#title' => $this->t('Ethnicity'),
            '#default_value' => '',
            '#options' => $ethnicty_options,
            '#default_value' => $member->ethnicity,
        );

        $smtdirectoryoptions = array('smtdirectory' => $this->t('Publish information in SMT member directory'));

        $form['smtprofile_smtdirectoryoptions'] = array(
            '#title' => 'SMT member directory',
            '#type' => 'checkboxes',
            '#options' => $smtdirectoryoptions,
            '#default_value' => $member->smtdirectory ? array('smtdirectory') : '',
            '#prefix' => '<hr>',
        );
        $form['smtprofile_items'] = array(
            '#type' => 'select',
            '#title' => $this->t('Member type'),
            '#options' => $membertypeoptions,
            '#default_value' => $member->items,
        );
        $form['smtprofile_payment_status'] = array(
            '#type' => 'select',
            '#title' => $this->t('Payment status'),
            '#options' => array(
                '' => $this->t(''),
                'Completed' => $this->t('Completed'),
                'Failed' => $this->t('Failed'),
                'Pending' => $this->t('Pending')
            ),
            '#default_value' => $member->payment_status,
        );

        $form['smtprofile_shippingoptions'] = array(
            '#type' => 'select',
            '#title' => $this->t('Shipping options'),
            '#options' => array(
                '' => $this->t(''),
                'print' => $this->t('Ship print copy'),
                'noprint' => $this->t('Do not ship a print copy')
            ),
            '#default_value' => $member->shippingoptions,
        );
        $form['smtprofile_payment_amount'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Payment amount'),
            '#default_value' => $member->payment_amount,
        );
        $form['smtprofile_Date_renewed'] = array(
            '#type' => 'date',
            '#title' => $this->t('Membership Expiration Date'),
            '#default_value' => $member->Date_renewed,
        );
        $form['smtprofile_date_created'] = array(
            '#type' => 'date',
            '#title' => $this->t('Date created'),
            '#default_value' => $member->date_created,
            '#disabled' => 'disabled',
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Update member')
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

        $id = $form_state->getValue('smtprofile_ID');
        $member = MemberUtils::member($id);

        if (empty($member)) {
            drupal_set_message('Member ID empty or not numeric! SMT records could not be updated!', 'error');
            return;
        }

        $db = Database::getConnection();

        $gender = $form_state->getValue('smtprofile_gender');
        $gender_explain = $form_state->getValue('smtprofile_gender_explain');

        if (isset($gender['Prefer not to answer'])) {
            $gender = '';
            $gender_explain = '';
        }
        else {
            $gender = implode(',', $gender);
        }

        $smtdirectory = 0;

        if (in_array('smtdirectory', array_filter($form_state->getValue('smtprofile_smtdirectoryoptions')))) {
            $smtdirectory = 1;
        }

        $sql = "UPDATE members SET fname = ?, lname = ?, title = ?, add1 = ?, add2 = ?, add3 = ?, city = ?,
        state = ?, zip = ?, country = ?, dept = ?, phone = ?, items = ?, gender = ?, gender_explain = ?, fname2 = ?, lname2 = ?, ethnicity = ?, rank = ?, payment_status = ?, Date_renewed = ?, date_created = ?, shippingoptions = ?, payment_amount = ?, smtdirectory = ? WHERE ID = ?";

        $db->query($sql, array($form_state->getValue('smtprofile_fname'), $form_state->getValue('smtprofile_lname'),
            $form_state->getValue('smtprofile_title'), $form_state->getValue('smtprofile_add1'),
            $form_state->getValue('smtprofile_add2'), $form_state->getValue('smtprofile_add3'),
            $form_state->getValue('smtprofile_city'), $form_state->getValue('smtprofile_state'),
            $form_state->getValue('smtprofile_zip'), $form_state->getValue('smtprofile_country'),
            $form_state->getValue('smtprofile_dept'), $form_state->getValue('smtprofile_phone'),
            $form_state->getValue('smtprofile_items'), $gender, $gender_explain,
            $form_state->getValue('smtprofile_fname2'), $form_state->getValue('smtprofile_lname2'),
            $form_state->getValue('smtprofile_ethnicity'), $form_state->getValue('smtprofile_rank'),
            $form_state->getValue('smtprofile_payment_status'), $form_state->getValue('smtprofile_Date_renewed'),
            $form_state->getValue('smtprofile_date_created'),$form_state->getValue('smtprofile_shippingoptions'),
            $form_state->getValue('smtprofile_payment_amount'), $smtdirectory, $id));

        drupal_set_message('Member ID ' . $id . ' successfully updated.');
    }

}
