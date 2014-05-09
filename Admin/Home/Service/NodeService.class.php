<?php
namespace Home\Service;

/**
 * NodeService
 */
class NodeService extends CommonService {
    /**
     * 节点类型
     * @var array
     */
    private $NODE_TYPE = array(
        1 => '应用（GROUP）',
        2 => '模块（MODULE）',
        3 => '操作（ACTION）'
    );

    /**
     * 得到带又层级的node数据
     * @return array
     */
    public function getNodes() {
        $category = new \Org\Util\Category($this->getModelName(),
                                           array('id', 'pid','title'));
        return $category->getList();
    }

    /**
     * 得到节点的类型
     * @param  int    $type 节点的类型
     * @return string
     */
    public function getNodeType($type) {
        return $this->NODE_TYPE[$type];
    }

    /**
     * 添加模块管理节点
     * @param  string $name     节点名称
     * @param  string $ctrlName 控制器名称
     * @return boolean
     */
    public function addModuleNodes($name, $ctrlName) {
        $Node = $this->getM();

        // 得到顶级节点的id
        $pNode = $Node->field('id')->getByPid(0);
        if (is_null($pNode)) {
            return false;
        }

        $Node->startTrans();
        $node = array(
            'status' => 1,
            'created_at' => time(),
            'updated_at' => time()
        );

        // 模块节点
        $mNode = array(
            'pid' => $pNode['id'],
            'title' => "{$name}管理",
            'name' => $ctrlName,
            'level' => 2
        );
        $ms = $Node->add(array_merge($mNode, $node));

        // 模块id
        $pid = $this->getM()->getLastInsId();
        // 操作节点
        $node['pid'] = $pid;
        $node['level'] = 3;

        // index
        $index = array('title' => "{$name}管理", 'name' => "index");
        // add
        $add = array('title' => "添加{$name}", 'name' => "add");
        // edit
        $edit = array('title' => "编辑{$name}", 'name' => "edit");
        // delete
        $delete = array('title' => "删除{$name}", 'name' =>"delete");

        $nodes = array(
            array_merge($node, $index),
            array_merge($node, $add),
            array_merge($node, $edit),
            array_merge($node, $delete)
        );

        $ns = $Node->addAll($nodes);
        if (false === $ms || false === $ns) {
            $Node->rollback();
            return false;
        }

        $Node->commit();
        return true;
    }

    /**
     * 删除模块管理节点
     * @param  string  $ctrlName 控制器名称
     * @return boolean
     */
    public function deleteModuleNodes($ctrlName) {
        $Node = $this->getM();
        $mNode = $Node->field('id')->getByName($ctrlName);

        if (is_null($mNode)) {
            return false;
        }

        $Node->delete($mNode['id']);
        $Node->where("pid={$mNode['id']}")->delete();

        return true;
    }

    /**
     * 设置节点状态
     * @param  int   $id     节点id
     * @param  int   $status 节点状态
     * @return mixed
     */
    public function setStatus($id, $status) {
        return $this->getM()
                    ->where("id={$id}")
                    ->save(array('status' => $status));
    }

    /**
     * 节点是否存在
     * @param  int     $id 节点id
     * @return boolean
     */
    public function existNode($id) {
        return !empty($this->getM()->getById($id));
    }

    protected function getModelName() {
        return 'Node';
    }
}