<?php
class Travel
{
    private $data;
    public function __construct() {
      $this->data = json_decode(file_get_contents('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels'), true);
    }

    public function totalPriceByCompanyId($company) {
      $price = 0;
      foreach($this->data as $item) {
        if($item['companyId'] == $company) $price += (float)$item['price'];
      }
      return $price;
    }
}
class Company
{
  private $data;
  private $travel;
  private $tree;
  private $total;
  private $result;
  public function __construct() {
    $this->data = json_decode(file_get_contents('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies'), true);
    $this->travel = new Travel();
  }
  public function getResult() {
    return $this->result;
  }

  private function processResult() {
    $result = [];
    foreach ($this->data as $v){
      $result[$v['parentId']][] = $v;
    }
    $this->result = $this->createTree($result, array($this->data[0]));
    krsort($this->tree);
  }

  private function createTree(&$array, $parent) {
    $tree = [];
    foreach ($parent as $k=>$v) {
      if(isset($array[$v['id']])) {
        $v['children'] = $this->createTree($array, $array[$v['id']]);
        foreach ($array[$v['id']] as $a) {
          $index = $number = $this->exportNumberInString($v['id']);
          $this->tree[$index] .= $a['id'] . '|';
        }
      }
      $tree[] = $v;
    } 
    return $tree;
  }

  private function totalParent() {
    $tmp = array_keys($this->tree);
    $last_key = end($tmp);
    foreach($this->tree as $k => $v) {
      if(!isset($this->total[$k])) $this->total[$k] = 0;
      $v = trim($v, '|');
      $array = explode('|', $v);
      $this->total[$k] += $this->travel->totalPriceByCompanyId('uuid-' . $k);
      foreach ($array as $item) {
        $number = $this->exportNumberInString($item);
        if(isset($this->total[$number])) $this->total[$k] += $this->total[$number];
        $this->total[$k] += $this->travel->totalPriceByCompanyId($item);
      }
    }
  }

  private function exportNumberInString($str) {
    return abs((int) filter_var($str, FILTER_SANITIZE_NUMBER_INT));
  }

  private function appendPrice() {
    $tree = [];
    foreach ($this->data as $item) {
      $number = $this->exportNumberInString($item['id']);
      $item['price'] = $this->travel->totalPriceByCompanyId($item['id']);
      if(isset($this->total[$number])) $item['price'] = $this->total[$number];
      $tree[] = $item;
    }
    $this->data = $tree;
  }

  private function filterElementLast() {
    foreach ($this->data as $item) {
      $index = $number = $this->exportNumberInString($item['id']);
      if(!isset($this->tree[$index])) $this->tree[$index] = '';
      foreach ($this->data as $v) {
        if($item['id'] == $v['parentId']) $this->tree[$index] .= $v['id'] . '|';
      }
    }
    $this->tree = array_filter($this->tree);
    krsort($this->tree);
  }

  public function main() {
    $this->filterElementLast();
    $this->totalParent();
    $this->appendPrice();
    $this->processResult();
  }
}
class TestScript
{
    public function execute()
    {
        $start = microtime(true);
        $company = new Company();
        $company->main();
        // var_dump($company->getResult());
        echo 'Total time: '.  (microtime(true) - $start);
    }
}
(new TestScript())->execute();