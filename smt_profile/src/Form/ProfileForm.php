<?php

/**
 * @file
 * Contains \Drupal\smt_profile\Form\ProfileForm.
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
class ProfileForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'smtprofile_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        if (MemberUtils::member()) {
            $tablename = "members";
        }
        else {
            $tablename = "smtprofiles";
            if (!MemberUtils::smtprofile_find_or_create_profile()) {
                drupal_set_message('Profile could not be found or created!', 'error');
                return '';
            }
        }

        $db = Database::getConnection();

        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $uid = $user->get('uid')->value;

        $sql = "SELECT * FROM {$tablename} s WHERE s.ID = :id";
        if ($uid == 1) $uid++;

        $data = $db->query($sql, array(':id' => $uid))->fetch();

        if (!$data) {
            drupal_set_message('Profile data could not be accessed!', 'error');
            return '';
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
            'Joint SMTmembersip, regular' => $this->t('Joint SMT membership, regular'),
            'Joint SMT membership, student/retired' => $this->t('Joint SMT membership, student/retired'),
            'SMT membership, regular' => $this->t('SMT membership, regular'),
            'SMT membership, student/retired' => $this->t('SMT membership, student/retired'),
            'Lifetime SMT membership' => $this->t('Lifetime SMT membership')
        );

        $form['#attached']['library'][] = 'smt_profile/smt_profile';

        $form['smtprofile_ID'] = array(
            '#type' => 'textfield',
            '#title' => 'SMT ID',
            '#value' => $uid,
            '#disabled' => 'disabled',
        );
        $form['smtprofile_title'] = array(
            '#type' => 'select',
            '#title' => $this->t('Title'),
            '#default_value' => '',
            '#options' => $titleoptions,
            '#default_value' => $data->title,
        );
        $form['smtprofile_fname'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('First name'),
            '#default_value' => $data->fname,
        );
        $form['smtprofile_lname'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Last name'),
            '#default_value' => $data->lname,
        );
        $form['smtprofile_add1'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address 1'),
            '#default_value' => $data->add1,
            '#prefix' => '<hr>',
        );
        $form['smtprofile_add2'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address 2 (if needed)'),
            '#default_value' => $data->add2,
        );
        $form['smtprofile_add3'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address 3 (if needed)'),
            '#default_value' =>  $data->add3,
        );
        $form['smtprofile_city'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('City'),
            '#default_value' => $data->city,
        );
        $form['smtprofile_state'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('State/Province'),
            '#default_value' => $data->state,
        );
        $form['smtprofile_zip'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('ZIP/Postal Code'),
            '#default_value' => $data->zip,
        );
        $form['smtprofile_country'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Country'),
            '#default_value' => $data->country,
        );

        $url = Url::fromRoute('entity.user.edit_form', array(
            'user' => $uid
        ));

        $form['smtprofile_email'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('E-mail (change your email ' . \Drupal::l(t('here'), $url) .')'),
            '#disabled' => 'disabled',
            '#default_value' => $user->get('mail')->value,
        );
        $form['smtprofile_phone'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Phone'),
            '#default_value' => $data->phone,
        );

        $form['smtprofile_employment'] = array(
            '#type' => 'select',
            '#title' => $this->t('Employment'),
            '#default_value' => '',
            '#options' => array('' => '', 'FT' => 'Full Time', 'PT' => 'Part Time', 'Unemployed' => 'Unemployed', 'Other' => 'Other'),
            '#default_value' => $data->employment,
            '#prefix' => '<hr>',
        );
        $form['smtprofile_dept'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Affiliation'),
            '#default_value' => $data->dept,
        );
        $form['smtprofile_rank'] = array(
            '#type' => 'select',
            '#title' => $this->t('Rank'),
            '#default_value' => 'other',
            '#options' => array(
                '' => '',
                'prof' => $this->t('Professor'),
                'assist_prof' => $this->t('Assistant Professor'),
                'assoc_prof' => $this->t('Associate Professor'),
                'grad' => $this->t('Graduate Student'),
                'undergrad' => $this->t('Undergraduate'),
                'retired' => $this->t('Retired'),
                'limited_year' => $this->t('Limited term, 1-year position'),
                'limited_continuing' => $this->t('Limited term, continuing appointment'),
                'other' => 'Other'
            ),
            '#default_value' => $data->rank,
        );
        $form['smtprofile_items'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Member Type'),
            '#disabled' => 'disabled',
            '#default_value' => $data->items,
        );

      //  if($data->items === 'Joint SMT membership, regular' || $data->items === 'Joint SMT member ship, student/retired') {
        $form['smtprofile_fname2'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Joint Member First Name'),
            '#default_value' => $data->fname2,
        );
        $form['smtprofile_lname2'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Joint Member Last Name'),
            '#default_value' => $data->lname2,
        );
      //  }

        $form['smtprofile_gender'] = array(
            '#type' => 'select',
            '#multiple' => true,
            '#title' => $this->t('Gender'),
            '#default_value' => '',
            '#options' => array('Woman' => $this->t('Woman'), 'Man' => $this->t('Man'), 'Trans' => $this->t('Trans/Transgender'), 'Another' => $this->t('Another identity'),'Prefer not to answer' => $this->t('Prefer not to answer')),
            '#default_value' => explode(',', $data->gender),
            '#prefix' => '<hr>',
        );
        $form['smtprofile_gender_explain'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Please specify:'),
            '#default_value' => $data->gender_explain,
        );
        $form['smtprofile_ethnicity'] = array(
            '#type' => 'select',
            '#title' => $this->t('Ethnicity'),
            '#default_value' => '',
            '#options' => array(
                'White' => $this->t('White'),
                'Black' => $this->t('Black'),
                'Hispan' => $this->t('Hispanic'),
                'AsianPac' => $this->t('Asian / Pacific islander'),
                'NatAm' => $this->t('Native American'),
                'FirstNation' => $this->t('First Nation'),
                'Mixed' => $this->t('Mixed Race'),
                'Unknown' => $this->t('Race / Ethnicity unknown'),
                'Prefer not to answer' => $this->t('Prefer not to answer')
            ),
            '#default_value' => $data->ethnicity,
        );

        $smtdirectoryoptions = array(
            'smtdirectory' => $this->t('Publish my information (name, affiliation, email) in SMT member directory and the SMT member blog listing'),
        );

        $form['smtprofile_smtdirectoryoptions'] = array(
            '#title' => 'SMT member directory',
            '#type' => 'checkboxes',
            '#options' => $smtdirectoryoptions,
            '#default_value' => array_keys($smtdirectoryoptions),
            '#prefix' => '<hr>',
        );

        $form['smtprofile_blog'] = array(
            '#title' => 'Blog URL',
            '#description' => $this->t('e.g. https://mywebsite.com/blog'),
            '#type' => 'textfield',
            '#default_value' => $data->blog,
        );

        $form['smtprofile_twitter'] = array(
            '#title' => 'Your Twitter username',
            '#description' => $this->t('e.g. joeexample'),
            '#type' => 'textfield',
            '#default_value' => $data->twitter,
        );

        $form['smtprofile_academia_edu'] = array(
            '#title' => 'Academia.edu username',
            '#description' => $this->t('e.g. JoeExample'),
            '#type' => 'textfield',
            '#default_value' => $data->academia_edu,
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Update my profile'),
            '#button_type' => 'primary',
        );

        return $form;

    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        // if (strlen($form_state->getValue('phone_number')) < 7) {
        //     $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
        // }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $uid = $form_state->getValue('smtprofile_ID');

        $tablename = "smtprofiles";
        if (MemberUtils::is_member()) {
            $tablename = "members";
        }

        $gender = $form_state->getValue('smtprofile_gender');
        $gender = implode(',', $gender) ;

        $gender_explain = $form_state->getValue('smtprofile_gender_explain');

        if (isset($gender['Prefer not to answer'])) {
            $gender = '';
            $gender_explain = '';
        }

        // update fields in members/smtprofile
        $smtdirectory = 0;
        if (in_array('smtdirectory', array_filter($form_state->getValue('smtprofile_smtdirectoryoptions')))) {
            $smtdirectory = 1;
        }

        $db = Database::getConnection();

        $sql = "UPDATE {$tablename} SET fname = ?, lname = ?, title = ?, add1 = ?, add2 = ?, add3 = ?, city = ?, state = ?, zip = ?, country = ?, dept = ?, phone = ?, fname2 = ?, lname2 = ?, gender = ?, ethnicity = ?, rank = ?, smtdirectory = ?, employment = ?, gender_explain = ?, blog = ?, academia_edu = ?, twitter = ? WHERE ID = ?";

        $data = $db->query($sql, array(
            trim($form_state->getValue('smtprofile_fname')), trim($form_state->getValue('smtprofile_lname')),
            $form_state->getValue('smtprofile_title'), $form_state->getValue('smtprofile_add1'),
            $form_state->getValue('smtprofile_add2'), $form_state->getValue('smtprofile_add3'),
            $form_state->getValue('smtprofile_city'), $form_state->getValue('smtprofile_state'),
            $form_state->getValue('smtprofile_zip'), $form_state->getValue('smtprofile_country'),
            $form_state->getValue('smtprofile_dept'), $form_state->getValue('smtprofile_phone'),
            trim($form_state->getValue('smtprofile_fname2')), trim($form_state->getValue('smtprofile_lname2')),
            $gender, $form_state->getValue('smtprofile_ethnicity'), $form_state->getValue('smtprofile_rank'),
            $smtdirectory, $form_state->getValue('smtprofile_employment'),
            $gender_explain, $form_state->getValue('smtprofile_blog'),
            $form_state->getValue('smtprofile_academia_edu'), $form_state->getValue('smtprofile_twitter'), $uid
        ));

        if (!$data) {
            drupal_set_message('There was an error. Your profile details were not saved.', 'error');
            return;
        }

        drupal_set_message('Your profile was saved successfully.');

    }

}
