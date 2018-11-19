<?php
namespace Models;

use \Core\Model;
use \Models\Jwt;
use \Models\Photos;

class Users extends Model {

	private $id_user;

	public function create($name, $email, $pass) {
		if(!$this->emailExists($email)) {
			if(!$this->nameExists($name)) {
				$hash = password_hash($pass, PASSWORD_DEFAULT);

				$sql = "INSERT INTO users (name, email, pass) VALUES (:name, :email, :pass)";
				$sql = $this->db->prepare($sql);
				$sql->bindValue(':name', $name);
				$sql->bindValue(':email', $email);
				$sql->bindValue(':pass', $hash);
				$sql->execute();

				$this->id_user = $this->db->lastInsertId();

				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function checkCredentials($email, $pass) {

		$sql = "SELECT id, pass FROM users WHERE email = :email";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':email', $email);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$info = $sql->fetch();

			if(password_verify($pass, $info['pass'])) {
				$this->id_user = $info['id'];

				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}

	}

	public function getId() {
		return $this->id_user;
	}

	//user

	public function getSearchUser($search) {
		$array = array('look'=>'', 'error'=>'');

		$sql = "SELECT id FROM users WHERE name LIKE :search AND id <> :meuid ORDER BY name";

		$sql = $this->db->prepare($sql);
		$sql->bindValue(':search', $search.'%');
		$sql->bindValue(':meuid', $this->getId());
		$sql->execute();

		if($sql->rowCount() > 0) {

			$array = $sql->fetchAll(\PDO::FETCH_ASSOC);

				foreach ($array as $k => $item) {

					$look = $this->getUserLikeUser($item['id']);

					$array[$k]['look'] = $look['look'];

					$user_info = $this->getInfo($item['id']);

					$array[$k]['name'] = $user_info['name'];
					$array[$k]['capa'] = $user_info['avatar'];
					$array[$k]['followers'] = $user_info['followers'];
				}
		}else{
			$array['error'] = 'Nenhum usuário encontrado!';
		}

		return $array;
	}

	public function getUserLikeUser($id_passive) {

		$data = array();
		$usuario = $this->getId();

		$sql = "SELECT id FROM users_following WHERE id_user_passive = :idpassive AND id_user_active = :iduser";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':idpassive', $id_passive);
		$sql->bindValue(':iduser', $usuario);
		$sql->execute();

		if($sql->rowCount() > 0) {
				$array['look'] = 'yes';
		}else{
			$array['look'] = 'no';
		}

		return $array;
	}

	public function getInfo($id) {
		$array = array();

		$sql = "SELECT id, name, email, avatar FROM users WHERE id = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id', $id);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$array = $sql->fetch(\PDO::FETCH_ASSOC);

			//$photos = new Photos();

			if(!empty($array['avatar'])) {
				$array['avatar'] = BASE_URL.'media/avatar/'.$array['avatar'];
			} else {
				$array['avatar'] = BASE_URL.'media/avatar/default.jpg';
			}

			$array['following'] = $this->getFollowingCount($id);
			$array['followers'] = $this->getFollowersCount($id);
			//$array['photos_count'] = $photos->getPhotosCount($id);
		}

		return $array;
	}

	public function getFollowingView($offset = 0, $per_page = 10) {
		$followingUsers = $this->getFollowing($this->getId());

		return $this->getFollowingInfo($followingUsers, $offset, $per_page);
	}

	public function getFollowing($id_user) {

		$data = array();

		$sql = "SELECT id_user_passive FROM users_following WHERE id_user_active = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id', $id_user);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$data = $sql->fetchAll();

			foreach ($data as $item) {
				$array[] = intval( $item['id_user_passive'] );
			}
		}

		return $array;
	}

	public function getFollowingInfo($ids, $offset, $per_page) {

		$array = array();

		if(count($ids) > 0) {

			$sql = "SELECT id
					FROM users
					WHERE id
					IN (".implode(',', $ids).")
					ORDER BY name
					LIMIT ".$offset.", ".$per_page;
			$sql = $this->db->query($sql);

			if($sql->rowCount() > 0) {
				$array = $sql->fetchAll(\PDO::FETCH_ASSOC);

				foreach ($array as $k => $item) {

					$user_info = $this->getInfo($item['id']);

					$array[$k]['name'] = $user_info['name'];
					$array[$k]['avatar'] = $user_info['avatar'];

				}
			}
		}

		return $array;
	}

	public function getFollowingCount($id_user) {
		$sql = "SELECT COUNT(*) as c FROM users_following WHERE id_user_active = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id', $id_user);
		$sql->execute();
		$info = $sql->fetch();

		return $info['c'];
	}

	public function getFollowersCount($id_user) {
		$sql = "SELECT COUNT(*) as c FROM users_following WHERE id_user_passive = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id', $id_user);
		$sql->execute();
		$info = $sql->fetch();

		return $info['c'];
	}

	//Games

	public function getSearchGame($search) {
		$array = array('look'=>'', 'error'=>'');

		$sql = "SELECT id FROM games WHERE name LIKE :search ORDER BY name";

		$sql = $this->db->prepare($sql);
		$sql->bindValue(':search', $search.'%');
		$sql->execute();

		if($sql->rowCount() > 0) {
			$array = $sql->fetchAll(\PDO::FETCH_ASSOC);

				foreach ($array as $k => $item) {

					$look = $this->getUserGamesLike($item['id']);

					$array[$k]['look'] = $look['look'];

					$game_info = $this->getGame($item['id']);

					$array[$k]['name'] = $game_info['name'];
					$array[$k]['capa'] = $game_info['capa'];
					$array[$k]['followersGame'] = $game_info['followersGame'];
				}
		}else{
			$array['error'] = 'Nenhum jogo encontrado!';
		}

		return $array;
	}

	public function getGame($id) {
		$array = array();

		$sql = "SELECT * FROM games WHERE id = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id', $id);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$array = $sql->fetch(\PDO::FETCH_ASSOC);


			if(!empty($array['capa'])) {
				$array['capa'] = BASE_URL.'media/capa/'.$array['capa'];
			}


			$array['followersGame'] = $this->getFollowersGameCount($id);

		}

		return $array;
	}

	public function getFollowersGameCount($id_game) {
		$sql = "SELECT COUNT(*) as g FROM users_games WHERE game_id = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id', $id_game);
		$sql->execute();
		$info = $sql->fetch();

		return $info['g'];
	}

	public function getGamesLikeView($offset = 0, $per_page = 10) {
		$gamesLike = $this->getGamesLike($this->getId());

		return $this->getGamesLikeInfo($gamesLike, $offset, $per_page);
	}

	public function getUserGamesLike($id_game) {

		$data = array();
		$usuario = $this->getId();

		$sql = "SELECT id FROM users_games WHERE game_id = :idgame AND user_id = :iduser";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':idgame', $id_game);
		$sql->bindValue(':iduser', $usuario);
		$sql->execute();

		if($sql->rowCount() > 0) {
				$array['look'] = 'yes';
		}else{
			$array['look'] = 'no';
		}

		return $array;
	}

	public function getGamesLike($id_user) {

		$data = array();

		$sql = "SELECT game_id FROM users_games WHERE user_id = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id', $id_user);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$data = $sql->fetchAll();

			foreach ($data as $item) {
				$array[] = intval( $item['game_id'] );
			}
		}

		return $array;
	}

	public function getGamesLikeInfo($ids, $offset, $per_page) {

		$array = array();

		if(count($ids) > 0) {

			$sql = "SELECT id
					FROM games
					WHERE id
					IN (".implode(',', $ids).")
					ORDER BY name
					LIMIT ".$offset.", ".$per_page;
			$sql = $this->db->query($sql);

			if($sql->rowCount() > 0) {
				$array = $sql->fetchAll(\PDO::FETCH_ASSOC);

				foreach ($array as $k => $item) {

					$game_info = $this->getGame($item['id']);

					$array[$k]['name'] = $game_info['name'];
					$array[$k]['capa'] = $game_info['capa'];
					$array[$k]['followersGame'] = $game_info['followersGame'];

				}
			}
		}

		return $array;
	}

	//Connects


	public function getUsersConnectCount($id_connect) {
		$sql = "SELECT COUNT(*) as g FROM users_connect WHERE connect_id = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id', $id_connect);
		$sql->execute();
		$info = $sql->fetch();

		return $info['g'];
	}

	public function getConnectView($offset = 0, $per_page = 10) {

		$gamesLike = $this->getGamesLike($this->getId());

		$connectGames = $this->getConnectLikeGames($gamesLike);

		return $this->getConnectInfo($connectGames, $offset, $per_page);
	}


	public function getConnectLikeGames($id_game) {

		$data = array();

		if(count($id_game) > 0) {


				$sql = "SELECT id FROM connect WHERE game_id IN (".implode(',', $id_game).")";
				$sql = $this->db->query($sql);

				if($sql->rowCount() > 0) {
					$data = $sql->fetchAll();
					foreach ($data as $item) {

					$array[] = intval( $item['id'] );

					}

				}
		}

		return $array;
	}

	public function getConnectInfo($ids, $offset, $per_page) {

		$array = array();

		if(count($ids) > 0) {

			$sql = "SELECT *
					FROM connect
					WHERE id
					IN (".implode(',', $ids).")
					ORDER BY date_create DESC
					LIMIT ".$offset.", ".$per_page;
			$sql = $this->db->query($sql);

			if($sql->rowCount() > 0) {
				$array = $sql->fetchAll(\PDO::FETCH_ASSOC);

				foreach ($array as $k => $item) {

					$game_info = $this->getGame($item['game_id']);

					$array[$k]['name'] = $game_info['name'];
					$array[$k]['capa'] = $game_info['capa'];

					$array[$k]['connectCount'] = $this->getUsersConnectCount($item['id']);

				}
			}
		}

		return $array;
	}

	//Token

	public function createJwt() {
		$jwt = new Jwt();
		return $jwt->create(array('id_user' => $this->id_user));
	}

	public function validateJwt($token) {
		$jwt = new Jwt();
		$info = $jwt->validate($token);

		if(isset($info->id_user)) {
			$this->id_user = $info->id_user;
			return true;
		} else {
			return false;
		}
	}

	private function emailExists($email) {
		$sql = "SELECT id FROM users WHERE email = :email";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':email', $email);
		$sql->execute();

		if($sql->rowCount() > 0) {
			return true;
		} else {
			return false;
		}
	}

	private function nameExists($name) {
		$sql = "SELECT id FROM users WHERE name = :name";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':name', $name);
		$sql->execute();

		if($sql->rowCount() > 0) {
			return true;
		} else {
			return false;
		}
	}

}


















