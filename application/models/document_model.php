<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 文件模块
 * @package iPlacard
 * @since 2.0
 */
class Document_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取文件信息
	 * @param int $id 用户ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_document($id, $part = '')
	{
		$this->db->where('document.id', intval($id));
		$this->db->join('document_file', 'document.file = file.id');
		$query = $this->db->get('document');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 查询符合条件的第一个文件ID
	 * @return int|false 符合查询条件的第一个文件ID，如不存在返回FALSE
	 */
	function get_document_id()
	{
		$args = func_get_args();
		array_unshift($args, 'document');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有文件ID
	 * @return array|false 符合查询条件的所有文件ID，如不存在返回FALSE
	 */
	function get_document_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'document');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 获取指定委员会的所有可查看文件
	 */
	function get_committee_documents($committee)
	{
		$this->db->where('access', array($committee, 0));
		$query = $this->db->get('document_access');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回文件ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['document'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 获取指定文件的访问范围
	 */
	function get_documents_accessibility($document)
	{
		$this->db->where('document', $document);
		$query = $this->db->get('document_access');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回文件ID
		foreach($query->result_array() as $data)
		{
			if($data['access'] == 0)
				return true;
			
			$array[] = $data['access'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 编辑/添加文件
	 * @return int 新的文件ID
	 */
	function edit_committee($data, $id = '')
	{
		//新增文件
		if(empty($id))
		{
			$this->db->insert('document', $data);
			return $this->db->insert_id();
		}
		
		//更新文件
		$this->db->where('id', $id);
		return $this->db->update('document', $data);
	}
	
	/**
	 * 添加文件
	 * @return int 新的文件ID
	 */
	function add_document($title, $description = '', $highlight = false, $user = '')
	{
		if(empty($user))
			$user = uid();
		
		$data = array(
			'title' => $title,
			'description' => $description,
			'highlight' => $highlight
		);
		
		//返回新文件ID
		return $this->edit_document($data);
	}
	
	/**
	 * 删除文件
	 * @param int $id 文件ID
	 * @return boolean 是否完成删除
	 */
	function delete_document($id)
	{
		$this->db->where('id', $id);
		$this->db->or_where('document', $id);
		return $this->db->delete(array('document', 'document_access', 'document_file'));
	}
	
	/**
	 * 添加访问权限
	 * @param int $document 文件ID
	 * @param int|array $committees 一个或一组委员会ID或0
	 * @return boolean 是否完成添加
	 */
	function add_access($document, $committees)
	{
		if(!is_array($committees))
			$committees = array($committees);
		
		//生成数据
		foreach($committees as $committee)
		{
			$data[] = array(
				'document' => $document,
				'access' => $committee
			);
		}
		
		return $this->db->insert_batch('document_access', $data);
	}
	
	/**
	 * 删除访问权限
	 * @param int $document 文件ID
	 * @param int|array $committees 一个或一组委员会ID或0
	 * @return boolean 是否完成删除
	 */
	function delete_access($document, $committees)
	{
		$this->db->where('document', $document);
		$this->db->where('access', $committees);
		return $this->db->delete('document_access');
	}
	
	/**
	 * 检查指定的委员会是否可访问指定文件
	 * @return boolean
	 */
	function is_accessible($document, $committee)
	{
		$access = $this->get_documents_accessibility($document);
		
		if($access === true)
			return true;
		
		if(in_array($committee, $access))
			return true;
		return false;
	}
	
	/**
	 * 检查文件是否可全局访问
	 * @return boolean
	 */
	function is_global_accessible($document)
	{
		$access = $this->get_documents_accessibility($document);
		
		if($access === true)
			return true;
		return false;
	}
}

/* End of file document_model.php */
/* Location: ./application/models/document_model.php */