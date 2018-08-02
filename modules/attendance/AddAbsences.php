<?php

#**************************************************************************
#  openSIS is a free student information system for public and non-public 
#  schools from Open Solutions for Education, Inc. web: www.os4ed.com
#
#  openSIS is  web-based, open source, and comes packed with features that 
#  include student demographic info, scheduling, grade book, attendance, 
#  report cards, eligibility, transcripts, parent portal, 
#  student portal and more.   
#
#  Visit the openSIS web site at http://www.opensis.com to learn more.
#  If you have question regarding this system or the license, please send 
#  an email to info@os4ed.com.
#
#  This program is released under the terms of the GNU General Public License as  
#  published by the Free Software Foundation, version 2 of the License. 
#  See license.txt.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#***************************************************************************************
include('../../RedirectModulesInc.php');
define('LANG_RECORDS_ADDED_CONFIRMATION', 'Absence records were added for the selected students.');
define('LANG_CHOOSE_STUDENT_ERROR', 'You must choose at least one period and one student.');
define('LANG_ABSENCE_CODE', 'Absence Code');
define('LANG_ABSENCE_REASON', 'Absence Reason');
if (!$_REQUEST['month'])
    $_REQUEST['month'] = date("m");
else
    $_REQUEST['month'] = MonthNWSwitch($_REQUEST['month'], 'tonum');
if (!$_REQUEST['year'])
    $_REQUEST['year'] = date("Y");
else
    $_REQUEST['year'] = ($_REQUEST['year'] < 1900 ? '20' . $_REQUEST['year'] : $_REQUEST['year']);


if (optional_param('modfunc', '', PARAM_NOTAGS) == 'save') {

    if (count($_REQUEST['period']) && count($_REQUEST['student']) && count($_REQUEST['dates'])) {
        $not_taken_arr = array();
        $taken_arr = array();
        foreach ($_REQUEST['period'] as $period_id => $yes)
            $periods_list .= ",'" . $period_id . "'";
        $periods_list = '(' . substr($periods_list, 1) . ')';

        foreach ($_REQUEST['student'] as $student_id => $yes)
            $students_list .= ",'" . $student_id . "'";
        $students_list = '(' . substr($students_list, 1) . ')';

        $current_RET = DBGet(DBQuery('SELECT STUDENT_ID,PERIOD_ID,COURSE_PERIOD_ID,SCHOOL_DATE,ATTENDANCE_CODE FROM attendance_period WHERE EXTRACT(MONTH FROM SCHOOL_DATE)=\'' . ($_REQUEST['month'] * 1) . '\' AND EXTRACT(YEAR FROM SCHOOL_DATE)=\'' . $_REQUEST[year] . '\' AND PERIOD_ID IN ' . $periods_list . ' AND STUDENT_ID IN ' . $students_list . ''), array(), array('STUDENT_ID', 'SCHOOL_DATE', 'PERIOD_ID', 'COURSE_PERIOD_ID'));

//                print_r($current_RET);    
        $cp_arr = array();
        foreach ($_REQUEST['student'] as $student_id => $yes) {
            foreach ($_REQUEST['dates'] as $date => $yes) {
                $current_mp = GetCurrentMP('QTR', $date);
                if (!$current_mp)
                    $current_mp = GetCurrentMP('SEM', $date);
                if (!$current_mp)
                    $current_mp = GetCurrentMP('FY', $date);

                $all_mp = GetAllMP(GetMPTable(GetMP($current_mp, 'TABLE')), $current_mp);


                $course_periods_RET = DBGet(DBQuery('SELECT s.COURSE_PERIOD_ID,cpv.PERIOD_ID,cpv.id as cpv_id FROM schedule s,course_periods cp,course_period_var cpv,attendance_calendar ac,school_periods sp WHERE sp.PERIOD_ID=cpv.PERIOD_ID AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID AND ac.SCHOOL_DATE=\'' . date('Y-m-d', strtotime($date)) . '\' AND ac.CALENDAR_ID=cp.CALENDAR_ID AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL) AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND s.STUDENT_ID=' . $student_id . ' AND cpv.PERIOD_ID IN ' . $periods_list . ' AND cpv.DOES_ATTENDANCE=\'Y\' AND (ac.SCHOOL_DATE BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND ac.SCHOOL_DATE>=s.START_DATE)) AND position(substring(\'UMTWHFS\' FROM DAYOFWEEK(ac.SCHOOL_DATE)  FOR 1) IN cpv.DAYS)>0 AND (cp.MARKING_PERIOD_ID IN (' . $all_mp . ') OR cp.MARKING_PERIOD_ID IS NULL) AND (s.MARKING_PERIOD_ID IN (' . $all_mp . ') OR s.MARKING_PERIOD_ID IS NULL) AND NOT (cp.HALF_DAY=\'Y\' AND (SELECT STATE_CODE FROM attendance_codes WHERE ID=\'' . optional_param('absence_code', '', PARAM_NUMBER) . '\')=\'H\')'), array());


                $c = 0;
                foreach ($course_periods_RET as $course_periods_RET) {
//                                    	
                    foreach ($_REQUEST['period'] as $period_id => $yes) {
                        $course_period_id = $course_periods_RET['COURSE_PERIOD_ID'];
//                               
                        $cp_arr[$course_periods_RET['CPV_ID']] = $course_period_id;
                        if (!$current_RET[$student_id][$date][$period_id][$course_period_id]) {

                            if ($course_period_id) {
                                $att_dup = DBQuery('delete from attendance_period where student_id=' . $student_id . ' and school_date=' . $date . ' and period_id=' . $period_id . '');
                                $sql = 'INSERT INTO attendance_period (STUDENT_ID,SCHOOL_DATE,PERIOD_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID,ATTENDANCE_CODE,ATTENDANCE_TEACHER_CODE,ATTENDANCE_REASON,ADMIN)values(\'' . $student_id . '\',\'' . $date . '\',\'' . $period_id . '\',\'' . $current_mp . '\',\'' . $course_period_id . '\',\'' . optional_param('absence_code', '', PARAM_NUMBER) . '\',\'' . optional_param('absence_code', '', PARAM_NUMBER) . '\',\'' . optional_param('absence_reason', '', PARAM_SPCL) . '\',\'Y\')';

                                DBQuery($sql);
                                $taken_arr[$student_id] = $student_id;
                            } else {
                                $not_taken_arr[$student_id] = $student_id;
                            }
                        } else {

                            $sql = 'UPDATE attendance_period SET ATTENDANCE_CODE=\'' . optional_param('absence_code', '', PARAM_NUMBER) . '\',ATTENDANCE_TEACHER_CODE=\'' . optional_param('absence_code', '', PARAM_NUMBER) . '\',ATTENDANCE_REASON=\'' . optional_param('absence_reason', '', PARAM_SPCL) . '\',ADMIN=\'Y\'
								WHERE STUDENT_ID=\'' . $student_id . '\' AND SCHOOL_DATE=\'' . $date . '\' AND PERIOD_ID=\'' . $period_id . '\'';
                            DBQuery($sql);
                            $taken_arr[$student_id] = $student_id;
                        }
                    }
                    $c++;
                }

                $val = optional_param('absence_reason', '', PARAM_SPCL);

                UpdateAttendanceDaily($student_id, $date, ($val ? $val : false));
            }
        }
        //-----------------------For update attendance_completed----------------------------------------

        foreach ($cp_arr as $cpv_id => $cp_id) {
            $current_RET = DBGet(DBQuery('SELECT STUDENT_ID,PERIOD_ID,SCHOOL_DATE,ATTENDANCE_CODE FROM attendance_period WHERE course_period_id=' . $cp_id . ' AND EXTRACT(MONTH FROM SCHOOL_DATE)=\'' . ($_REQUEST['month'] * 1) . '\' AND EXTRACT(YEAR FROM SCHOOL_DATE)=\'' . $_REQUEST[year] . '\''), array(), array('SCHOOL_DATE', 'PERIOD_ID'));
            foreach ($_REQUEST['dates'] as $date => $yes) {

                $course_periods_RET = DBGet(DBQuery('SELECT s.COURSE_PERIOD_ID,cpv.PERIOD_ID,cp.TEACHER_ID FROM schedule s,course_periods cp,course_period_var cpv,attendance_calendar ac,school_periods sp WHERE sp.PERIOD_ID=cpv.PERIOD_ID AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID AND ac.SCHOOL_DATE=\'' . date('Y-m-d', strtotime($date)) . '\' AND ac.CALENDAR_ID=cp.CALENDAR_ID AND (ac.BLOCK=sp.BLOCK OR sp.BLOCK IS NULL) AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID=' . $cp_id . ' AND cpv.DOES_ATTENDANCE=\'Y\' AND (ac.SCHOOL_DATE BETWEEN s.START_DATE AND s.END_DATE OR (s.END_DATE IS NULL AND ac.SCHOOL_DATE>=s.START_DATE)) AND position(substring(\'UMTWHFS\' FROM DAYOFWEEK(ac.SCHOOL_DATE)  FOR 1) IN cpv.DAYS)>0 AND cp.MARKING_PERIOD_ID IN (' . $all_mp . ') AND s.MARKING_PERIOD_ID IN (' . $all_mp . ') AND NOT (cp.HALF_DAY=\'Y\' AND (SELECT STATE_CODE FROM attendance_codes WHERE ID=\'' . optional_param('absence_code', '', PARAM_NUMBER) . '\')=\'H\')'), array(), array('PERIOD_ID'));

                foreach ($_REQUEST['period'] as $period_id => $yes) {

                    $attn_taken = count($current_RET[$date][$period_id]);
                    $attn_possible = count($course_periods_RET[$period_id]);

                    if ($attn_possible == $attn_taken) {
                        if ($attn_possible > 0) {
                            $RET = DBGet(DBQuery('SELECT \'' . 'completed' . '\' AS COMPLETED FROM attendance_completed WHERE STAFF_ID=\'' . $course_periods_RET[$period_id][1]['TEACHER_ID'] . '\' AND SCHOOL_DATE=\'' . $date . '\' AND PERIOD_ID=\'' . $period_id . '\''));
                            if (!count($RET))
                                DBQuery('INSERT INTO attendance_completed (STAFF_ID,SCHOOL_DATE,PERIOD_ID,COURSE_PERIOD_ID,CPV_ID) values(\'' . $course_periods_RET[$period_id][1]['TEACHER_ID'] . '\',\'' . $date . '\',\'' . $period_id . '\',\'' . $cp_id . '\',\'' . $cpv_id . '\')');
                        }
                    }
                }
            }
        }
        //---------------------------------------------------------------
        unset($_REQUEST['modfunc']);
        $array_diff = array_diff($not_taken_arr, $taken_arr);
        $error_note = '';
        if (count($array_diff) == 0)
            $note = LANG_RECORDS_ADDED_CONFIRMATION;
        if (count($array_diff) > 0 && count($taken_arr) == 0) {
            $note = '';
            $error_note = '&nbsp;Unable to add absence records for <br/>';
            foreach ($array_diff as $st_id) {
                $get_stu_names = DBGet(DBQuery('SELECT CONCAT(LAST_NAME,\', \',FIRST_NAME) AS FULL_NAME FROM students WHERE STUDENT_ID=' . $st_id));
                $error_note .= $get_stu_names[1]['FULL_NAME'] . '<br/>';
            }
        }
        if (count($array_diff) > 0 && count($taken_arr) > 0) {
            $note = LANG_RECORDS_ADDED_CONFIRMATION;
            $error_note = '&nbsp;Unable to add absence records for <br/>';
            foreach ($array_diff as $st_id) {
                $get_stu_names = DBGet(DBQuery('SELECT CONCAT(LAST_NAME,\', \',FIRST_NAME) AS FULL_NAME FROM students WHERE STUDENT_ID=' . $st_id));
                $error_note .= $get_stu_names[1]['FULL_NAME'] . '<br/>';
            }
        }
    } else {
        echo '<font color=red>' . LANG_CHOOSE_STUDENT_ERROR . '</font>';
        for_error_sch();
    }
}



if (!$_REQUEST['modfunc']) {
    $extra['link'] = array('FULL_NAME' => false);
    $extra['SELECT'] = ",NULL AS CHECKBOX";

    if (optional_param('search_modfunc', '', PARAM_NOTAGS) == 'list') {
        echo "<FORM class=\"form-horizontal\" action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=save  METHOD=POST name=addAbsences>";

        PopTable_wo_header('header');

        echo '<div class="row">';
        echo '<div class="col-lg-12">';
        
        echo '<div class="form-group">';
        echo '<label class="control-label col-lg-2">Add absence to periods</label>';
        echo '<div class="col-lg-10">';

        $periods_RET = DBGet(DBQuery('SELECT SHORT_NAME,PERIOD_ID FROM school_periods WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND EXISTS (SELECT * FROM course_periods cp,course_period_var cpv WHERE cpv.PERIOD_ID=school_periods.PERIOD_ID AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID AND cpv.DOES_ATTENDANCE=\'' . 'Y' . '\') ORDER BY SORT_ORDER'));
        foreach ($periods_RET as $period)
            echo '<label class="checkbox-inline"><INPUT type=CHECKBOX value=Y name=period[' . $period['PERIOD_ID'] . ']>' . $period['SHORT_NAME'] . '</label>';
        echo '</div>'; //.col-lg-8
        echo '</div>'; //.form-group
        
        echo '<div class="row">';
        echo '<div class="col-lg-6">';
        echo '<div class="form-group">';
        echo '<label class="control-label col-lg-3">Absence code</label>';
        echo '<div class="col-lg-9">';
        echo '<SELECT class="form-control" name=absence_code>';
        $codes_RET = DBGet(DBQuery('SELECT TITLE,ID FROM attendance_codes WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND TABLE_NAME=0'));
        foreach ($codes_RET as $code)
            echo '<OPTION value=' . $code['ID'] . '>' . $code['TITLE'] . '</OPTION>';
        echo '</SELECT>';
        echo '</div>'; //.col-lg-8
        echo '</div>'; //.form-group
        echo '</div>'; //.col-lg-6
        
        echo '<div class="col-lg-6">';
        echo '<div class="form-group">';
        echo '<label class="control-label col-lg-3">Absence reason</label>';
        echo '<div class="col-lg-9">';
        echo '<INPUT type=text name=absence_reason class="form-control">';
        echo '</div>'; //.col-lg-8
        echo '</div>'; //.form-group
        echo '</div>'; //.col-lg-6
        echo '</div>'; //.row
        
        echo '</div>'; //.col-lg-12
        echo '<div class="col-lg-12">';



        $time = mktime(0, 0, 0, $_REQUEST['month'] * 1, 1, substr($_REQUEST['year'], 2));
        echo '<div class="clearfix"><div class="col-md-12"><div class="form-inline">' . PrepareDate(strtoupper(date("d-M-y", $time)), '', false, array('M' => 1, 'Y' => 1, 'submit' => true)) . '</div></div></div>';
        echo '<br/>';
        $skip = date("w", $time);
        $last = 31;
        while (!checkdate($_REQUEST['month'] * 1, $last, substr($_REQUEST['year'], 2)))
            $last--;

        echo '<div class="table-responsive"><table class="table table-bordered table-condensed" width="100%"><thead><tr>';
        echo '<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr></thead><tbody><tr>';
        $calendar_RET = DBGet(DBQuery('SELECT SCHOOL_DATE FROM attendance_calendar WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND MINUTES!=0 AND EXTRACT(MONTH FROM SCHOOL_DATE)=\'' . ($_REQUEST['month'] * 1) . '\''), array(), array('SCHOOL_DATE'));
        for ($i = 1; $i <= $skip; $i++)
            echo '<td class="alpha-grey"></td>';

        $j = 1;
        for ($i = 1; $i <= $last; $i++) {
            $this_date = $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-' . ($i < 10 ? '0' . $i : $i);
            if (!$calendar_RET[$this_date])
                $disabled = ' DISABLED';
            elseif (date('Y-m-d') == $this_date)
                $disabled = ' CHECKED';
            else
                $disabled = '';

            echo '<td align=left><label class="checkbox-inline"><INPUT type=checkbox name=dates[' . $this_date . '] value=Y' . $disabled . '>' . $i . '</label></td>';
            $skip++;
            if ($skip % 7 == 0 && $i != $last) {
                echo '</tr><tr>';
                $j = 0;
            }
            $j++;
        }
        $k = $j - 1;
        if ($k < 7) {
            for ($i = 1; $i <= (7-$k); $i++) {
                echo '<t class="alpha-grey"></TD>';
            }
        }
        echo '</tr></tbody></table></div>';
        echo '</div>'; //.col-lg-6
        echo '</div>'; //.row
        Poptable('footer');
    } elseif ($note)
        DrawHeader('<IMG SRC=assets/check.gif>' . $note);
    if ($error_note)
        DrawHeader('<IMG SRC=assets/warning_button.gif>' . $error_note);


    $extra['search'] .= '<div class="row">';
    $extra['search'] .= '<div class="col-lg-6">';
    Widgets('course');
    $extra['search'] .= '</div>'; //.col-lg-6
    $extra['search'] .= '<div class="col-lg-6">';
    Widgets('activity');
    $extra['search'] .= '</div>';
    $extra['search'] .= '</div>'; //.row

    $extra['search'] .= '<div class="row">';
    $extra['search'] .= '<div class="col-lg-6">';
    $extra['search'] .= '<div class="well mb-20 pt-5 pb-5">';
    $extra['search'] .= '<div class="pl-10">';
    Widgets('absences');
    $extra['search'] .= '</div>'; //.well
    $extra['search'] .= '</div>'; //.pl-10
    $extra['search'] .= '</div>'; //.col-lg-6
    $extra['search'] .= '</div>'; //.row

    $extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
    $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
    $extra['new'] = true;

    Search('student_id', $extra);

    if (optional_param('search_modfunc', '', PARAM_ALPHA) == 'list')
        echo SubmitButton(Save, '', 'class="btn btn-primary" onclick="formload_ajax(\'addAbsences\');"') . "</FORM>";
    
echo '<div id="modal_default" class="modal fade">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h5 class="modal-title">Choose course</h5>
</div>

<div class="modal-body">';
echo '<center><div id="conf_div"></div></center>';
echo'<table id="resp_table"><tr><td valign="top">';
echo '<div>';
   $sql = "SELECT SUBJECT_ID,TITLE FROM course_subjects WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TITLE";
$QI = DBQuery($sql);
$subjects_RET = DBGet($QI);

echo count($subjects_RET). ((count($subjects_RET)==1)?' Subject was':' Subjects were').' found.<br>';
if(count($subjects_RET)>0)
{
echo '<table class="table table-bordered"><tr class="bg-grey-200"><th>Subject</th></tr>'; 
foreach($subjects_RET as $val)
{
echo '<tr><td><a href=javascript:void(0); onclick="chooseCpModalSearch('.$val['SUBJECT_ID'].',\'courses\')">'.$val['TITLE'].'</a></td></tr>';
}
echo '</table>';
}
echo '</div></td>';
echo '<td valign="top"><div id="course_modal"></div></td>';
echo '<td valign="top"><div id="cp_modal"></div></td>';
echo '</tr></table>';
//         echo '<div id="coursem"><div id="cpem"></div></div>';
echo' </div>
</div>
</div>
</div>';
}

function _makeChooseCheckbox($value, $title) {
    global $THIS_RET;

    return "<INPUT type=checkbox name=student[" . $THIS_RET['STUDENT_ID'] . "] value=Y>";
}

?>
