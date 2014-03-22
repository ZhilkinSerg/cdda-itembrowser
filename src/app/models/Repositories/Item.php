<?php 
namespace Repositories;

class Item
{
  protected $repo;
  protected $types;

  public function __construct(RepositoryInterface $repo)
  {
    $this->repo = $repo;
    $this->types = array_flip(array(
      "AMMO", "GUN", "ARMOR", "TOOL", "TOOL_ARMOR", "BOOK", "COMESTIBLE",
      "CONTAINER", "GUNMOD", "GENERIC", "BIONIC_ITEM", "VAR_VEH_PART",
      "_SPECIAL",
    ));
    $this->book_types = array(
        "archery"=>"range", 
        "handguns"=>"range", 
        "markmanship"=>"range",
        "launcher"=>"range", 
        "firearms"=>"range", 
        "throw"=>"range", 
        "rifle"=>"range",
        "shotgun"=>"range", 
        "smg"=>"range", 
        "pistol"=>"range", 
        "gun"=>"range",
        "bashing"=>"combat", 
        "cutting"=>"combat", 
        "stabbing"=>"combat", 
        "dodge"=>"combat",
        "melee"=>"combat", 
        "unarmed"=>"combat",
        "computer"=>"engineering", 
        "electronics"=>"engineering", 
        "fabrication"=>"engineering",
        "mechanics"=>"engineering", 
        "construction"=>"engineering", 
        "carpentry"=>"engineering",
        "traps"=>"engineering",
        "tailor"=>"crafts",
        "firstaid"=>"crafts",
        "cooking"=>"crafts",
        "barter"=>"social", 
        "speech"=>"social",
        "driving"=>"survival", 
        "survival"=>"survival", 
        "swimming"=>"survival",
        "none"=>"fun",
    );
    \Event::listen("cataclysm.newObject", function ($repo, $object) {
      $this->getIndexes($repo, $object);
    });
  }


  private function getIndexes($repo, $object)
  {
    if (!isset($this->types[$object->type]))
      return;
    $repo->addIndex("item", $object->id, $object->repo_id);
    if ($object->type=="_SPECIAL")
      return;
    if ($object->bashing+$object->cutting>10 and $object->to_hit>-2) {
      $repo->addIndex("melee", $object->id, $object->repo_id);
    }
    if ($object->type=="ARMOR" and !isset($object->covers)) {
      $repo->addIndex("armor.none", $object->id, $object->repo_id);
    } 
    else if ($object->type=="ARMOR" and isset($object->covers)) {
      foreach($object->covers as $part) {
        $part = strtolower($part);
        $repo->addIndex("armor.$part", $object->id, $object->repo_id);
      }
    }
    if ($object->type=="CONTAINER")
      $repo->addIndex("container", $object->id, $object->repo_id);
    if ($object->type=="COMESTIBLE")
      $repo->addIndex("food", $object->id, $object->repo_id);
    if ($object->type=="TOOL")
      $repo->addIndex("tool", $object->id, $object->repo_id);
    if ($object->type=="BOOK") {
      if(isset($this->book_types[$object->skill])) {
        $skill = $this->book_types[$object->skill];
        $repo->addIndex("book.$skill", $object->id, $object->repo_id);
      } else 
        $repo->addIndex("book.other", $object->id, $object->repo_id);
    }
    if ($object->type=="GUN") {
      $repo->addIndex("gun.$object->skill", $object->id, $object->repo_id);
    }
    if ($object->type=="AMMO") {
      $repo->addIndex("ammo.$object->ammo_type", $object->id, $object->repo_id);
    }
    if ($object->type=="COMESTIBLE") {
      $type = strtolower($object->comestible_type);
      $repo->addIndex("comestible.$type", $object->id, $object->repo_id);
    }
  }

  public function find($id)
  {
    $item = \App::make('Item');
    $data = $this->repo->get("item", $id);
    $item->load($data?:
      json_decode('{"id":"'.$id.'","name":"'.$id.'?","type":"invalid"}')
    );
    return $item;
  }

  public function findOr404($id)
  {
    $item = $this->find($id);
    if($item->type=="invalid")
      \App::abort(404);
    return $item;
  }

  public function where($text)
  {
    \Log::info("searching for $text...");

    $results = array();
    if (!$text)
      return $results;
    foreach($this->all() as $item) {
      if ($item->matches($text)) {
        $results[] = $item;
      }
    }
    return $results;
  }

  public function all()
  {
    $ret = array();
    foreach($this->repo->all("item") as $id=>$item) {
      $ret[$id] = $this->find($id);
    }
    return $ret;
  }

  public function index($name)
  {
    $ret = array();
    foreach($this->repo->all($name) as $id=>$item) {
      $ret[$id] = $this->find($id);
    }
    return $ret;
  }

  public function indexRaw($index)
  {
    return $this->repo->all($index);
  }
}
