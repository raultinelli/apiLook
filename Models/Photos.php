<?php
namespace Models;

use \Core\Model;

class Photos extends Model {

	public function getPhotosCount($id_user) {
		$sql = "SELECT COUNT(*) as c FROM photos WHERE id_user = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id', $id_user);
		$sql->execute();
		$info = $sql->fetch();

		return $info['c'];
	}

}