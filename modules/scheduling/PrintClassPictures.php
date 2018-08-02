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
if ($_REQUEST['modfunc'] == 'save') {
    if (count($_REQUEST['cp_arr'])) {
        $cp_list = '\'' . implode('\',\'', $_REQUEST['cp_arr']) . '\'';

        $course_periods_RET = DBGet(DBQuery('SELECT cp.COURSE_PERIOD_ID,cp.TITLE,TEACHER_ID FROM course_periods cp,course_period_var cpv WHERE cp.COURSE_PERIOD_ID IN (' . $cp_list . ') AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID ORDER BY (SELECT SORT_ORDER FROM school_periods WHERE PERIOD_ID=cpv.PERIOD_ID)'));

        if ($_REQUEST['include_teacher'] == 'Y')
            $teachers_RET = DBGet(DBQuery('SELECT STAFF_ID,LAST_NAME,FIRST_NAME FROM staff WHERE STAFF_ID IN (SELECT TEACHER_ID FROM course_periods WHERE COURSE_PERIOD_ID IN (' . $cp_list . '))'), array(), array('STAFF_ID'));
        $handle = PDFStart();
        $PCP_UserCoursePeriod = $_SESSION['UserCoursePeriod']; // save/restore for teachers

        foreach ($course_periods_RET as $course_period) {
            $course_period_id = $course_period['COURSE_PERIOD_ID'];
            $teacher_id = $course_period['TEACHER_ID'];

            if ($teacher_id) {
                $_openSIS['User'] = array(1 => array('STAFF_ID' => $teacher_id, 'NAME' => 'name', 'PROFILE' => 'teacher', 'SCHOOLS' => ',' . UserSchool() . ',', 'SYEAR' => UserSyear()));
                $_SESSION['UserCoursePeriod'] = $course_period_id;

                $extra = array('SELECT_ONLY' => 's.STUDENT_ID,s.LAST_NAME,s.FIRST_NAME', 'ORDER_BY' => 's.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME');
                $RET = GetStuList($extra);
                if (count($RET)) {
                    echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
                    echo "<tr><td width=105>" . DrawLogo() . "</td><td  style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">Student Class Pictures</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                    echo '<TABLE border=0 style=border-collapse:collapse>';
                    echo '<TR><TD colspan=5 align=center  style=font-size:15px; font-weight:bold;>' . UserSyear() . '-' . (UserSyear() + 1) . ' - ' . $course_period['TITLE'] . '</TD></TR>';
                    $i = 0;
                    if ($_REQUEST['include_teacher'] == 'Y') {
                        $teacher = $teachers_RET[$teacher_id][1];

                        echo '<TR><TD valign=bottom><TABLE>';
                        if ($UserPicturesPath && (($size = getimagesize($picture_path = $UserPicturesPath . '/' . $teacher_id . '.JPG')) || $_REQUEST['last_year'] == 'Y' && $staff['ROLLOVER_ID'] && ($size = getimagesize($picture_path = $UserPicturesPath . (UserSyear() - 1) . '/' . $staff['ROLLOVER_ID'] . '.JPG'))))
                            if ($size[1] / $size[0] > 172 / 130)
                                echo '<TR><TD><IMG SRC="' . $picture_path . '" width=144></TD></TR>';
                            else
                                echo '<TR><TD><IMG SRC="' . $picture_path . '" width=144></TD></TR>';
                        else
                            echo '<TR><TD><img src="assets/noimage.jpg" width=144></TD></TR>';
                        echo '<TR><TD><FONT size=-1><B>' . $teacher['LAST_NAME'] . '</B><BR>' . $teacher['FIRST_NAME'] . '</FONT></TD></TR>';
                        echo '</TABLE></TD>';
                        $i++;
                    }

                    foreach ($RET as $student) {
                        $student_id = $student['STUDENT_ID'];

                        if ($i++ % 5 == 0)
                            echo '<TR>';

                        echo '<TD valign=bottom><TABLE>';
                        if ($StudentPicturesPath && (($size = getimagesize($picture_path = $StudentPicturesPath . '/' . $student_id . '.JPG')) || $_REQUEST['last_year'] == 'Y' && ($size = getimagesize($picture_path = $StudentPicturesPath . '/' . $student_id . '.JPG'))))
                            if ($size[1] / $size[0] > 144 / 144)
                                echo '<TR><TD><IMG SRC="' . $picture_path . '" width=144></TD></TR>';
                            else
                                echo '<TR><TD><IMG SRC="' . $picture_path . '" width=144></TD></TR>';
                        else
                            echo '<TR><TD><img src="assets/noimage.jpg" width=144></TD></TR>';
                        echo '<TR><TD><FONT size=-1><B>' . $student['LAST_NAME'] . '</B><BR>' . $student['FIRST_NAME'] . '</FONT></TD></TR>';
                        echo '</TABLE></TD>';

                        if ($i % 5 == 0)
                            echo '</TR><!-- NEED 2in -->';
                    }
                    if ($i % 5 != 0)
                        echo '</TR>';
                    echo '</TABLE>';
                    echo "<div style=\"page-break-before: always;\"></div>";
                }
            }
        }
        $_SESSION['UserCoursePeriod'] = $PCP_UserCoursePeriod;
        PDFStop($handle);
    } else
        BackPrompt('You must choose at least one course period.');
}


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
    echo '<tr><td><a href=javascript:void(0); onclick="MassDropModal('.$val['SUBJECT_ID'].',\'courses\')">'.$val['TITLE'].'</a></td></tr>';
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
if (!$_REQUEST['modfunc']) {
    DrawBC("Scheduling > " . ProgramTitle());

    if (User('PROFILE') != 'admin')
        $_REQUEST['search_modfunc'] = 'list';

    if ($_REQUEST['search_modfunc'] == 'list') {

        echo "<FORM name=inc id=inc action=ForExport.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=save&_openSIS_PDF=true method=POST target=_blank>";
        echo '<div class="panel panel-default">';

        $extra['extra_header_left'] = '<label class="checkbox-inline"><INPUT type=checkbox name=include_teacher value=Y checked>Include Teacher</label>';
        $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT type=checkbox name=legal_size value=Y>Legal Size Paper</label>';
        $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT type=checkbox name=last_year value=Y>Use Last Year\'s if Missing</label>';
        if (User('PROFILE') == 'admin' || User('PROFILE') == 'teacher')
            $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT type=checkbox name=include_inactive value=Y>Include Inactive Students</label>';
    }

    mySearch('course_period', $extra);
    if ($_REQUEST['search_modfunc'] == 'list') {

        if ($_SESSION['count_course_periods'] != 0)
            echo '<div class="panel-footer"><div class="heading-elements"><span class="heading-text no-margin-top"><INPUT type=submit class="btn btn-primary" value=\'Create Class Pictures for Selected Course Periods\'></span></div></div>';
        
        echo '</div>';
        echo '</FORM>';
    }
}

function mySearch($type, $extra = '') {
    global $extra;

    if (($_REQUEST['search_modfunc'] == 'search_fnc' || !$_REQUEST['search_modfunc'])) {
        
        echo "<FORM class=\"form-horizontal\" action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=" . strip_tags(trim($_REQUEST[modfunc])) . "&search_modfunc=list&next_modname=" . strip_tags(trim($_REQUEST[next_modname])) . " method=POST>";
        
        PopTable('header', 'Search');

        $RET = DBGet(DBQuery('SELECT s.STAFF_ID,CONCAT(s.LAST_NAME,\'' . ',' . '\',s.FIRST_NAME) AS FULL_NAME FROM staff s,staff_school_relationship ssr WHERE s.STAFF_ID=ssr.STAFF_ID AND s.PROFILE=\'' . 'teacher' . '\' AND position(\'' . UserSchool() . '\' IN ssr.SCHOOL_ID)>0 AND ssr.SYEAR=\'' . UserSyear() . '\' ORDER BY FULL_NAME'));
        echo '<div class="row">';
        echo '<div class="col-lg-6">';
        echo '<div class="form-group"><label class="control-label col-lg-4">Teacher</label><div class="col-lg-8">';
        echo "<SELECT name=teacher_id class=form-control><OPTION value=''>N/A</OPTION>";
        foreach ($RET as $teacher)
            echo "<OPTION value=$teacher[STAFF_ID]>$teacher[FULL_NAME]</OPTION>";
        echo '</SELECT></div></div>';
        echo '</div>'; //.col-lg-6

        $RET = DBGet(DBQuery('SELECT SUBJECT_ID,TITLE FROM course_subjects WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' ORDER BY TITLE'));
        echo '<div class="col-lg-6">';
        echo '<div class="form-group"><label class="control-label col-lg-4">Subject</label><div class="col-lg-8">';
        echo "<SELECT name=subject_id class=\"form-control\"><OPTION value=''>N/A</OPTION>";
        foreach ($RET as $subject)
            echo "<OPTION value=$subject[SUBJECT_ID]>$subject[TITLE]</OPTION>";
        echo '</SELECT></div></div>';
        echo '</div>'; //.col-lg-6
        echo '</div>'; //.row

        $RET = DBGet(DBQuery('SELECT PERIOD_ID,TITLE FROM school_periods WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' ORDER BY SORT_ORDER'));
        echo '<div class="row">';
        echo '<div class="col-lg-6">';
        echo '<div class="form-group"><label class="control-label col-lg-4">Period</label><div class="col-lg-8">';
        echo "<SELECT name=period_id class=\"form-control\"><OPTION value=''>N/A</OPTION>";
        foreach ($RET as $period)
            echo "<OPTION value=$period[PERIOD_ID]>$period[TITLE]</OPTION>";
        echo '</SELECT></div></div>';
        echo '</div>'; //.col-lg-6
        
        echo '<div class="col-lg-6">';
        Widgets('course');
        echo $extra['search'];
        echo '</div>'; //.col-lg-6
        echo '</div>'; //.row
        

        echo '<div>';
        echo Buttons('Submit', 'Reset');
        echo '</div>';
        PopTable('footer');
        
        echo '</FORM>';
    } else {
        DrawHeader('', $extra['header_right']);
        DrawHeader($extra['extra_header_left'], $extra['extra_header_right']);

        if (User('PROFILE') == 'admin') {
            if ($_REQUEST['teacher_id'])
                $where .= ' AND cp.TEACHER_ID=\'' . $_REQUEST[teacher_id] . '\'';
            if ($_REQUEST['first'])
                $where .= ' AND UPPER(s.FIRST_NAME) LIKE \'' . strtoupper($_REQUEST['first']) . '%' . '\'';
            if ($_REQUEST['w_course_period_id'])
                if ($_REQUEST['w_course_period_id_which'] == 'course')
                    $where .= ' AND cp.COURSE_ID=(SELECT COURSE_ID FROM course_periods WHERE COURSE_PERIOD_ID=\'' . $_REQUEST['w_course_period_id'] . '\')';
                else
                    $where .= ' AND cp.COURSE_PERIOD_ID=\'' . $_REQUEST['w_course_period_id'] . '\'';
            if ($_REQUEST['subject_id']) {
                $from .= ',courses c';
                $where .= ' AND c.COURSE_ID=cp.COURSE_ID AND c.SUBJECT_ID=\'' . $_REQUEST['subject_id'] . '\'';
            }
            if ($_REQUEST['period_id'])
                $where .= " AND cpv.PERIOD_ID='" . $_REQUEST['period_id'] . "'";

            $sql = 'SELECT cp.COURSE_PERIOD_ID,cp.TITLE,sp.ATTENDANCE FROM course_periods cp,course_period_var cpv,school_periods sp' . $from . ' WHERE cp.SCHOOL_ID=\'' . UserSchool() . '\' AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID AND cp.SYEAR=\'' . UserSyear() . '\' AND sp.PERIOD_ID=cpv.PERIOD_ID' . $where . '';
        }
        elseif (User('PROFILE') == 'teacher') {
            $sql = 'SELECT cp.COURSE_PERIOD_ID,cp.TITLE,sp.ATTENDANCE FROM course_periods cp,course_period_var cpv,school_periods sp WHERE cp.SCHOOL_ID=\'' . UserSchool() . '\' AND cp.SYEAR=\'' . UserSyear() . '\' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\' AND sp.PERIOD_ID=cpv.PERIOD_ID AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID';
        } else {
            $sql = 'SELECT cp.COURSE_PERIOD_ID,cp.TITLE,sp.ATTENDANCE FROM course_periods cp,course_period_var cpv,school_periods sp,schedule ss WHERE cp.SCHOOL_ID=\'' . UserSchool() . '\' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.SYEAR=\'' . UserSyear() . '\' AND ss.STUDENT_ID=\'' . UserStudentID() . '\' AND (CURRENT_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR CURRENT_DATE<=ss.END_DATE)) AND sp.PERIOD_ID=cpv.PERIOD_ID AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID';
        }
        $sql .= ' GROUP BY cp.COURSE_PERIOD_ID ORDER BY sp.PERIOD_ID';

        $course_periods_RET = DBGet(DBQuery($sql), array('COURSE_PERIOD_ID' => '_makeChooseCheckbox'));
        $_SESSION['count_course_periods'] = count($course_periods_RET);
        $LO_columns = array('COURSE_PERIOD_ID' => '</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'cp_arr\');"><A>', 'TITLE' => 'Course Period');
        ListOutput($course_periods_RET, $LO_columns, 'Course Period', 'Course Periods');
    }
}

function _makeChooseCheckbox($value, $title) {
    global $THIS_RET;

    return "<INPUT type=checkbox name=cp_arr[] value=$value" . ($THIS_RET['ATTENDANCE'] == 'Y' ? ' checked' : '') . ">";
}

?>