<?php
namespace Repositories;

class Quality
{
  protected $database;

  const DEFAULT_INDEX = "tool_quality";
  const ID_FIELD = "id";

  public function __construct()
  {
    \Event::listen("cataclysm.newObject", function ($repo, $object) {
      $this->getIndexes($repo, $object);
    });
  }

  private function getIndexes($repo, $object)
  {
    if ($object->type=="tool_quality")
    {
      $repo->addIndex(self::DEFAULT_INDEX, $object->id, $object->repo_id);
    }
  }

  public function model()
  {
    return \App::make("Quality");
  }
}
