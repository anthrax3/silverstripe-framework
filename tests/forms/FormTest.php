<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class FormTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/Forms/FormTest.yml';
	
	public function testLoadDataFromRequest() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldSet(
				new TextField('key1'),
				new TextField('namespace[key2]'),
				new TextField('namespace[key3][key4]'),
				new TextField('othernamespace[key5][key6][key7]')
			),
			new FieldSet()
		);
		
		// url would be ?key1=val1&namespace[key2]=val2&namespace[key3][key4]=val4&othernamespace[key5][key6][key7]=val7
		$requestData = array(
			'key1' => 'val1',
			'namespace' => array(
				'key2' => 'val2',
				'key3' => array(
					'key4' => 'val4',
				)
			),
			'othernamespace' => array(
				'key5' => array(
					'key6' =>array(
						'key7' => 'val7'
					)
				)
			)
		);
		
		$form->loadDataFrom($requestData);
		
		$fields = $form->Fields();
		$this->assertEquals($fields->fieldByName('key1')->Value(), 'val1');
		$this->assertEquals($fields->fieldByName('namespace[key2]')->Value(), 'val2');
		$this->assertEquals($fields->fieldByName('namespace[key3][key4]')->Value(), 'val4');
		$this->assertEquals($fields->fieldByName('othernamespace[key5][key6][key7]')->Value(), 'val7');
	}
	
	public function testLoadDataFromUnchangedHandling() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldSet(
				new TextField('key1'),
				new TextField('key2')
			),
			new FieldSet()
		);
		$form->loadDataFrom(array(
			'key1' => 'save',
			'key2' => 'dontsave',
			'key2_unchanged' => '1'
		));
		$this->assertEquals(
			$form->getData(), 
			array(
				'key1' => 'save',
				'key2' => null,
			),
			'loadDataFrom() doesnt save a field if a matching "<fieldname>_unchanged" flag is set'
		);
	}
	
	public function testLoadDataFromObject() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldSet(
				new HeaderField('My Player'),
				new TextField('Name'), // appears in both Player and Team
				new TextareaField('Biography'),
				new DateField('Birthday'),
				new NumericField('BirthdayYear') // dynamic property
				//new CheckboxSetField('Teams') // relation editing
			),
			new FieldSet()
		);
		
		$captain1 = $this->objFromFixture('FormTest_Player', 'captain1');
		$form->loadDataFrom($captain1);
		$this->assertEquals(
			$form->getData(), 
			array(
				'Name' => 'Captain 1',
				'Biography' => 'Bio 1',
				'Birthday' => '1982-01-01', 
				'BirthdayYear' => '1982', 
			),
			'LoadDataFrom() loads simple fields and dynamic getters'
		);

		$captain2 = $this->objFromFixture('FormTest_Player', 'captain2');
		$form->loadDataFrom($captain2);
		$this->assertEquals(
			$form->getData(), 
			array(
				'Name' => 'Captain 2',
				'Biography' => 'Bio 1',
				'Birthday' => '1982-01-01', 
				'BirthdayYear' => '1982', 
			),
			'LoadNonBlankDataFrom() loads only fields with values, and doesnt overwrite existing values'
		);
		
		$captain2 = $this->objFromFixture('FormTest_Player', 'captain2');
		$form->loadDataFrom($captain2, true);
		$this->assertEquals(
			$form->getData(), 
			array(
				'Name' => 'Captain 2',
				'Biography' => '',
				'Birthday' => '', 
				'BirthdayYear' => 0, 
			),
			'LoadDataFrom() overwrites existing with blank values on subsequent calls'
		);
	}
	
	public function testFormMethodOverride() {
		$form = $this->getStubForm();
		$form->setFormMethod('GET');
		$this->assertNull($form->dataFieldByName('_method'));
		
		$form = $this->getStubForm();
		$form->setFormMethod('PUT');
		$this->assertEquals($form->dataFieldByName('_method')->Value(), 'put',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'post',
			'PUT override in forms has POST in <form> tag'
		);
		
		$form = $this->getStubForm();
		$form->setFormMethod('DELETE');
		$this->assertEquals($form->dataFieldByName('_method')->Value(), 'delete',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'post',
			'PUT override in forms has POST in <form> tag'
		);
	}
	
	protected function getStubForm() {
		return new Form(
			new Controller(),
			'Form',
			new FieldSet(new TextField('key1')),
			new FieldSet()
		);
	}
	
}

class FormTest_Player extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'Biography' => 'Text',
		'Birthday' => 'Date'
	);
	
	static $belongs_many_many = array(
		'Teams' => 'FormTest_Team'
	);
	
	static $has_one = array(
		'FavouriteTeam' => 'FormTest_Team', 
	);
	
	public function getBirthdayYear() {
		return ($this->Birthday) ? date('Y', strtotime($this->Birthday)) : null;
	}
	
}

class FormTest_Team extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'Region' => 'Varchar',
	);
	
	static $many_many = array(
		'Players' => 'FormTest_Player'
	);
}
?>