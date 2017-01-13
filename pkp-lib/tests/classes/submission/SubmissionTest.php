<?php
/**
 * @file tests/classes/article/SubmissionTest.inc.php
 *
 * Copyright (c) 2013-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionTest
 * @ingroup tests_classes_submission
 * @see Submission
 *
 * @brief Test class for the Submission class
 */
import('lib.pkp.tests.PKPTestCase');
class SubmissionTest extends PKPTestCase {
	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		$this->submission = $this->_getSubmission();
	}
	/**
	 * @see PKPTestCase::tearDown()
	 */
	protected function tearDown() {
		unset($this->submission);
	}
	//
	// Unit tests
	//
	/**
	 * @covers Submission
	 */
	public function testPageArray() {
		$expected = array(array('i', 'ix'), array('6', '11'), array('19'), array('21'));
		// strip prefix and spaces
		$this->submission->setPages('pg. i-ix, 6-11, 19, 21');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// no spaces
		$this->submission->setPages('i-ix,6-11,19,21');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// double-hyphen
		$this->submission->setPages('i--ix,6--11,19,21');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// single page
		$expected = array(array('16'));
		$this->submission->setPages('16');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// spaces in a range
		$expected = array(array('16', '20'));
		$this->submission->setPages('16 - 20');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// pages are alphanumeric
		$expected = array(array('a6', 'a12'), array('b43'));
		$this->submission->setPages('a6-a12,b43');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// inconsisent formatting
		$this->submission->setPages('pp:  a6 -a12,   b43');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		$this->submission->setPages('  a6 -a12,   b43 ');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// empty-ish values
		$expected = array();
		$this->submission->setPages('');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		$this->submission->setPages(' ');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		$expected = array(array('0'));
		$this->submission->setPages('0');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
	}

	/**
	 * Return the application specific submission object.
	 * @return Submission|null
	 */
	function _getSubmission() {
		$submissionDAO = Application::getSubmissionDAO();
		if (is_a($submissionDAO, 'ArticleDAO')) {
			import('classes.article.Article');
			return new Article();
		} elseif (is_a($submissionDAO, 'MonographDAO')) {
			import('classes.monograph.Monograph');
			return new Monograph();
		}
		return null;
	}
}
?>