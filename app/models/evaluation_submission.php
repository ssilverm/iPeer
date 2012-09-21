<?php
class EvaluationSubmission extends AppModel
{
  var $name = 'EvaluationSubmission';

  var $belongsTo = array(
                   'Event' =>
                     array(
                     'className' => 'Event',
                     'foreignKey' => 'event_id'
                     )
                 );

	function getEvalSubmissionByGrpEventIdSubmitter($grpEventId=null, $submitter=null){
		return $this->find('grp_event_id='.$grpEventId.' AND submitter_id='.$submitter);
	}

	function getEvalSubmissionByEventIdSubmitter($eventId=null, $submitter=null) {
	  return $this->find('event_id='.$eventId.' AND submitter_id='.$submitter);
	}

	function getSubmittersByEventId($eventId=null) {
        $submitters = $this->generateList('event_id='.$eventId, 'submitter_id ASC', null, '{n}.EvaluationSubmission.submitter_id', '{n}.EvaluationSubmission.submitter_id');
        if ($submitters == null) {
            $submitters = array();
        }

        return array_keys($submitters);
	}

	function getSubmittersByGroupEventId($groupEventId=null) {
        $submitters = $this->generateList('grp_event_id='.$groupEventId, 'submitter_id ASC', null, '{n}.EvaluationSubmission.submitter_id', '{n}.EvaluationSubmission.submitter_id');
        if ($submitters == null) {
            $submitters = array();
        }

        return array_keys($submitters);
	}

  // check if an entire group has completed all the evaluations
	// for a particular assignment
	function numInGroupCompleted($groupId=null, $groupEventId=null) {
        $condition = 'EvaluationSubmission.submitted = 1 AND GroupMember.group_id='.$groupId.' AND EvaluationSubmission.grp_event_id='.$groupEventId;
        $fields = 'GroupMember.user_id, EvaluationSubmission.submitter_id, EvaluationSubmission.submitted';
        $joinTable = array(' LEFT JOIN groups_members as GroupMember ON GroupMember.user_id=EvaluationSubmission.submitter_id');

        return $this->findAll($condition, $fields, null, null, null, null, $joinTable );
	}

	function numCountInGroupCompleted($groupId=null, $groupEventId=null) {
        $condition = 'EvaluationSubmission.submitted = 1 AND GroupMember.group_id='.$groupId.' AND EvaluationSubmission.grp_event_id='.$groupEventId;
        $fields = 'Count(EvaluationSubmission.submitter_id) AS count';
        $joinTable = array(' LEFT JOIN groups_members as GroupMember ON GroupMember.user_id=EvaluationSubmission.submitter_id');
        return $this->findAll($condition, $fields, null, null, null, null, $joinTable );
	}

	function numCountInEventCompleted($eventId=null) {
        $condition = 'EvaluationSubmission.submitted = 1 AND EvaluationSubmission.event_id='.$eventId;
        $fields = 'Count(EvaluationSubmission.submitter_id) AS count';
        $joinTable = array(' LEFT JOIN groups_members as GroupMember ON GroupMember.user_id=EvaluationSubmission.submitter_id');

       // return $this->findAll($condition, $fields, null, null, null, null, $joinTable );
       return $this-> findAll($condition, $fields, null, null, null, null );

	}

}

