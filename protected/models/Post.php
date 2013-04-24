<?php
/**
* This is the model class for table "tbl_post".
*
* The followings are the available columns in table 'tbl_post':
* @property integer $id
* @property string $title
* @property string $content
* @property string $tags
* @property integer $status
* @property integer $create_time
* @property integer $update_time
* @property integer $author_id
*
* The followings are the available model relations:
* @property Comment[] $comments
* @property User $author
*/
class Post extends CActiveRecord
{
	
	const STATUS_DRAFT=1;
	const STATUS_PUBLISHED=2;
	const STATUS_ARCHIVED=3;
	
	
	/**
	* Returns the static model of the specified AR class.
	* @return Post the static model class
	*/
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	/**
	* @return string the associated database table name
	*/
	public function tableName()
	{
		return 'tbl_post';
	}
	
	protected function afterSave()
	{
		parent::afterSave();
		Tag::model()->updateFrequency($this->_oldTags, $this->tags);
	}
	
	private $_oldTags;
	
	protected function afterFind()
	{
		parent::afterFind();
		$this->_oldTags=$this->tags;
	}
	/*
	In the implementation, because we want to detect if the user changes the tags in case he is updating an existing post, we need to know what the old tags are. For this reason, we also write the afterFind() method to keep the old tags in the variable _oldTags. The method afterFind() is invoked automatically by Yii when an AR record is populated with the data from database.
	
	
	*/	
	
	protected function beforeSave()
	{
		if(parent::beforeSave())
		{
			if($this->isNewRecord)
			{
				$this->create_time=$this->update_time=time();
				$this->author_id=Yii::app()->user->id;
			}
			else
			$this->update_time=time();
			return true;
		}
		else
		return false;
	}
	
	protected function afterDelete()
	{
		parent::afterDelete();
		Comment::model()->deleteAll('post_id='.$this->id);
		Tag::model()->updateFrequency($this->tags, '');
	}
	
	
	/**
	* @return array validation rules for model attributes.
	*/
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		/*return array(
		array('title, content, status, author_id', 'required'),
		array('status, create_time, update_time, author_id', 'numerical', 'integerOnly'=>true),
		array('title', 'length', 'max'=>128),
		array('tags', 'safe'),
		// The following rule is used by search().
		// Please remove those attributes that should not be searched.
		array('id, title, content, tags, status, create_time, update_time, author_id', 'safe', 'on'=>'search'),
		);*/
		return array(
		array('title, content, status', 'required'),
		array('title', 'length', 'max'=>128),
		array('status', 'in', 'range'=>array(1,2,3)),
		array('tags', 'match', 'pattern'=>'/^[\w\s,]+$/',
		'message'=>'Tags can only contain word characters.'),
		array('tags', 'normalizeTags'),
		array('title, status', 'safe', 'on'=>'search'),
		);
	}
	
	public function normalizeTags($attribute,$params)
	{
		$this->tags=Tag::array2string(array_unique(Tag::string2array($this->tags)));
	}
	
	/**
	* @return array a list of links that point to the post list filtered by every tag of this post
	*/
	public function getTagLinks()
	{
		$links=array();
		foreach(Tag::string2array($this->tags) as $tag)
			$links[]=CHtml::link(CHtml::encode($tag), array('post/index', 'tag'=>$tag));
		return $links;
	}
	
	public function addComment($comment)
	{
		if(Yii::app()->params['commentNeedApproval'])
			$comment->status=Comment::STATUS_PENDING;
		else
			$comment->status=Comment::STATUS_APPROVED;
		$comment->post_id=$this->id;
		return $comment->save();
	}
	
	
	/**
	* @return array relational rules.
	*/
	public function relations()
	{
		/*
		return array(
		'comments' => array(self::HAS_MANY, 'Comment', 'post_id'),
		'author' => array(self::BELONGS_TO, 'User', 'author_id'),
		);*/
		
		return array(
		'author' => array(self::BELONGS_TO, 'User', 'author_id'),
		'comments' => array(self::HAS_MANY, 'Comment', 'post_id',
		'condition'=>'comments.status='.Comment::STATUS_APPROVED,
		'order'=>'comments.create_time DESC'),
		'commentCount' => array(self::STAT, 'Comment', 'post_id',
		'condition'=>'status='.Comment::STATUS_APPROVED),
		);
		
	}
	
	public function getUrl()
	{
		return Yii::app()->createUrl('post/view', array(
		'id'=>$this->id,
		'title'=>$this->title,
		));
	}
	
	/**
	* @return array customized attribute labels (name=>label)
		*/
	public function attributeLabels()
	{
		return array(
		'id' => 'ID',
		'title' => 'Title',
		'content' => 'Content',
		'tags' => 'Tags',
		'status' => 'Status',
		'create_time' => 'Create Time',
		'update_time' => 'Update Time',
		'author_id' => 'Author',
		);
	}
	/**
	* Retrieves a list of models based on the current search/filter conditions.
	* @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	*/
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.
		$criteria=new CDbCriteria;
		$criteria->compare('id',$this->id);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('tags',$this->tags,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('author_id',$this->author_id);
		return new CActiveDataProvider(get_class($this), array(
		'criteria'=>$criteria,
		));
	}
}