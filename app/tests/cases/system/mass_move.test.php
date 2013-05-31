<?php
require_once('system_base.php');

class massMoveTestCase extends SystemBaseTestCase
{    
    protected $courseId;
    protected $eventId;

    public function startCase()
    {
        $this->getUrl();
        $wd_host = 'http://localhost:4444/wd/hub';
        $this->web_driver = new PHPWebDriver_WebDriver($wd_host);
        $this->session = $this->web_driver->session('firefox');
        $this->session->open($this->url);
        
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $this->session->deleteAllCookies();
        $login = PageFactory::initElements($this->session, 'Login');
        $home = $login->login('root', 'ipeeripeer');
    }
    
    public function endCase()
    {
        $this->session->deleteAllCookies();
        $this->session->close();
    }
    
    public function testImportUsers()
    {
        $this->courseId = $this->addCourse('TEST 101 101');
        $this->session->open($this->url.'courses/import');
        
        // check the submit button is disabled
        $submit = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'submit');
        $this->assertTrue($submit->attribute('disabled'));
        
        $file = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseFile');
        $file->sendKeys(dirname(__FILE__).'/files/massMove.csv');
        
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseIdentifiersUsername')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 
            'select[id="CourseSourceCourses"] option[value="1"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseSourceSurveys"] option')) - 1;  
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 
            'select[id="CourseSourceSurveys"] option[value="4"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseDestCourses"] option')) - 1;  
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR,
            'select[id="CourseDestCourses"] option[value="'.$this->courseId.'"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseAction0')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[type="submit"]')->click();
        
        
        // enrolling the instructor and tutor (csv) will fail
        $header = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'h3')->text();
        $this->assertEqual($header, 'User(s) failed to transfer:');
        
        // check that 7 students have been copied over to the new course
        $this->session->open($this->url.'users/goToClassList/'.$this->courseId);
        $classList = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'table_id_info')->text();
        $this->assertEqual($classList, 'Showing 1 to 7 of 7 entries');
        
        // check the event has been duplicated
        $this->session->open($this->url.'events/index/'.$this->courseId);
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Team Creation Survey')->click();
        $title = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "h1.title")->text();
        $this->assertEqual($title, 'TEST 101 101 - Demo Course > Team Creation Survey > View');
        $this->eventId = end(explode('/', $this->session->url()));
        
        // check that the two submissions have been copied over
        $this->session->open($this->url.'evaluations/viewSurveySummary/'.$this->eventId);
        $submitted = count($this->session->elements(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Result'));
        $this->assertEqual($submitted, 2);
    }
    

    public function testMassMoveError()
    {
        $this->session->open($this->url.'courses/import');
        $file = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseFile');
        $file->sendKeys(dirname(__FILE__).'/files/docx.docx');
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseIdentifiersUsername')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 
            'select[id="CourseSourceCourses"] option[value="1"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseSourceSurveys"] option')) - 1;  
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 
            'select[id="CourseSourceSurveys"] option[value="4"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseDestCourses"] option')) - 1;  
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR,
            'select[id="CourseDestCourses"] option[value="'.$this->courseId.'"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseAction0')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[type="submit"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'submit')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::ID, "flashMessage"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'flashMessage')->text();
        $this->assertEqual($msg, "extension is not allowed.\nFileUpload::processFile() - Unable to save temp file to file system.");
    }
    
    public function testImportByStudentNo()
    {
        $cId = $this->addCourse('TEST 201 101');
        // enrol two students
        $this->enrolStudent($cId, 'redshirt0001');
        $this->enrolStudent($cId, 'redshirt0002');
        $this->enrolStudent($cId, 'redshirt0003');

        $this->session->open($this->url.'courses/home/'.$cId);
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Add Event')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventTitle')->sendKeys('Test Survey');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="EventEventTemplateTypeId"] option[value="3"]')->click();
        //set due date and release date end to next month so that the event is opened.
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventDueDate')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'a[title="Next"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, '1')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventReleaseDateBegin')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventReleaseDateEnd')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'a[title="Next"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, '4')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();

        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        
        //fill in survey for one student
        $this->waitForLogoutLogin('redshirt0003');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Test Survey')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInput0ResponseId2')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInput1ResponseId5')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $this->waitForLogoutLogin('root');
        
        $this->session->open($this->url.'courses/import');
        $file = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseFile');
        $file->sendKeys(dirname(__FILE__).'/files/massMoveStudentNo.csv');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseSourceCourses"] option[value="'.$this->courseId.'"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseSourceSurveys"] option')) - 1;  
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseSourceSurveys"] option[value="'.$this->eventId.'"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseDestCourses"] option')) - 1;  
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseDestCourses"] option[value="'.$cId.'"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseDestSurveys"] option')) - 1;  
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseSurveyChoices0')->click(); //Existing Survey
        $survey = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseDestSurveys"] option');
        $survey[1]->click(); // choose the course's only survey
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "table[class='standardtable']"));
            }
        );

        $fail = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[1]/tbody/tr[2]/td[2]');
        $this->assertEqual($fail->text(), 'No student with student number 12345678 exists.');
        $fail = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[1]/tbody/tr[3]/td[2]');
        $this->assertEqual($fail->text(), 'No student with student number 87654321 exists.');
        
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[2]/td[1]');
        $this->assertEqual($success->text(), '65498451');
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[3]/td[1]');
        $this->assertEqual($success->text(), '65468188');
        
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[4]/td[1]');
        $this->assertEqual($success->text(), '98985481');
        // successful, but survey responses will not be moved/copied because they have already submitted
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[4]/td[2]')->text();
        $this->assertEqual(substr($success, 0, 46), 'Success. The student has already submitted to ');
        
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[5]/td[1]')->text();
        $this->assertEqual($success, '84188465');
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[5]/td[2]')->text();
        $this->assertEqual($success, 'Success. However no student with student number 84188465 was enrolled in the source course.');
        
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[6]/td[1]');
        $this->assertEqual($success->text(), '48877031');

        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[7]/td[1]');
        $this->assertEqual($success->text(), '37116036');
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[7]/td[2]')->text();
        $this->assertEqual($success, 'Success. However no student with student number 37116036 was enrolled in the source course.');

        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[8]/td[1]');
        $this->assertEqual($success->text(), '90938044');
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[9]/td[1]');
        $this->assertEqual($success->text(), '19524032');
        $success = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table[2]/tbody/tr[10]/td[1]');
        $this->assertEqual($success->text(), '10186039');
        
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Back to Course')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'List Students')->click();
        $total = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'table_id_info')->text();
        $this->assertEqual($total, 'Showing 1 to 9 of 9 entries');
        
        $this->session->open($this->url.'courses/home/'.$cId);
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'List Evaluation Events')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Results')->click();
        $results = $this->session->elements(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Result');
        $this->assertEqual(count($results), 2);
        
        $this->session->open($this->url.'courses/home/'.$this->courseId);
        $students = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="CourseHome"]/table/tbody/tr[2]/td[4]');
        $this->assertEqual($students->text(), '0 students');
        $events = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="CourseHome"]/table/tbody/tr[2]/td[6]');
        $this->assertEqual($events->text(), '1 events');
        
        $this->session->open($this->url.'courses/delete/'.$cId);
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'The course was deleted successfully.');
    }
    
    public function testDeleteCourse()
    {
        $this->session->open($this->url.'courses/delete/'.$this->courseId);
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'The course was deleted successfully.');
    }
    
    public function testBlankFile()
    {
        $this->courseId = $this->addCourse('DEMO 101 101');
        $this->session->open($this->url.'courses/import');
        $file = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseFile');
        $file->sendKeys(dirname(__FILE__).'/files/blank.csv');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 
            "select[id='CourseSourceCourses'] option[value='".$this->courseId."']")->click();
        // check that surveys fields are all disabled
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return $session->element(PHPWebDriver_WebDriverBy::ID, 'CourseSourceSurveys')->attribute('disabled');
            }
        );
        $help = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'noSourceSurvey')->text();
        $this->assertEqual($help, 'No surveys were found in the Source Course.');
        $help = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'noDestSurvey')->text();
        $this->assertEqual($help, 'No surveys were found in the Source Course.');
        $dSurvey = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseSurveyChoices1');
        $this->assertTrue($dSurvey->attribute('checked'));
        $this->assertTrue($dSurvey->attribute('disabled'));
        $dSurvey = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseSurveyChoices0');
        $this->assertTrue($dSurvey->attribute('disabled'));

        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR,
            "select[id='CourseDestCourses'] option[value='2']")->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseAction0')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "table[class='standardtable']"));
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Back to Course')->click();
        $students = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="CourseHome"]/table/tbody/tr[2]/td[4]');
        $this->assertEqual($students->text(), '15 students');
    }
    
    public function testWithoutSurvey()
    {
        $this->session->open($this->url.'courses/import');
        $file = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseFile');
        $file->sendKeys(dirname(__FILE__).'/files/fromAPSC201.csv');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR,
            "select[id='CourseSourceCourses'] option[value='2']")->click();
        // check that surveys fields are all disabled
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return $session->element(PHPWebDriver_WebDriverBy::ID, 'CourseSourceSurveys')->attribute('disabled');
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseIdentifiersUsername')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR,
            "select[id='CourseDestCourses'] option[value='".$this->courseId."']")->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseAction0')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "table[class='standardtable']"));
            }
        );
        $user = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[2]/td[1]');
        $this->assertEqual($user->text(),'redshirt0023');
        $note = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[2]/td[2]');
        $this->assertEqual($note->text(), 'Success.');
        $user = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[3]/td[1]');
        $this->assertEqual($user->text(),'redshirt0025');
        $note = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[3]/td[2]');
        $this->assertEqual($note->text(), 'Success.');
        $user = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[4]/td[1]');
        $this->assertEqual($user->text(),'redshirt0026');
        $note = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[4]/td[2]');
        $this->assertEqual($note->text(), 'Success.');
    }
    
    public function testDiffSuccessMsgs()
    {
        $this->session->open($this->url.'courses/import');
        $file = $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseFile');
        $file->sendKeys(dirname(__FILE__).'/files/fromMECH328.csv');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR,
            "select[id='CourseSourceCourses'] option[value='1']")->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseIdentifiersUsername')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="CourseSourceSurveys"] option')) - 1;  
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR,
            "select[id='CourseSourceSurveys'] option[value='4']")->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR,
            "select[id='CourseDestCourses'] option[value='".$this->courseId."']")->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "table[class='standardtable']"));
            }
        );
        $user = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[2]/td[1]');
        $this->assertEqual($user->text(),'redshirt0001');
        $note = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[2]/td[2]');
        $this->assertEqual($note->text(), 'Success. The student has already submitted a peer evaluation in the source course.');
        $user = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[3]/td[1]');
        $this->assertEqual($user->text(),'redshirt0004');
        $note = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[3]/td[2]');
        $this->assertEqual($note->text(), 'Success. However no student with username redshirt0004 was enrolled in the source course.');
        $user = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[4]/td[1]');
        $this->assertEqual($user->text(),'redshirt0005');
        $note = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[4]/td[2]');
        $this->assertEqual($note->text(), 'Success. However no student with username redshirt0005 was enrolled in the source course.');
        
        $this->enrolStudent(1, 'redshirt0001'); // re-enrol redshirt0001
        $this->assignToGroup();
        $this->session->open($this->url.'courses/delete/'.$this->courseId);
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'The course was deleted successfully.');
    }
    
    public function addCourse($name)
    {
        $this->session->open($this->url.'courses/add');
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseCourse')->sendKeys($name);
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'CourseTitle')->sendKeys('Demo Course');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[type="submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, $name)->click();
        
        return end(explode('/', $this->session->url()));
    }
    
    public function enrolStudent($cId, $username)
    {
        // enrol a student to test that an already enrolled student will still be imported "successfully"
        $this->session->open($this->url.'users/add/'.$cId);
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'UserUsername')->sendKeys($username);
        // wait for "username already exist" warning
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return $session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'div[id="usernameErr"]')->text();
            }
        );
        
        $warning = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'div[id="usernameErr"]')->text();
        $this->assertEqual(substr($warning, 0, 39), 'Username "'.$username.'" already exists.');
        
        // click here to enrol
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'here')->click();
        
        // wait for the student to be enrolled
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'User is successfully enrolled.');
    }

    public function assignToGroup()
    {
        $this->session->open($this->url.'groups/edit/1');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="all_groups"] option[value="5"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Assign >>"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Edit Group"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
    }
}