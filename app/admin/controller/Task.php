<?php
// +----------------------------------------------------------------------
// | Tplay [ WE ONLY DO WHAT IS NECESSARY ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://tplay.pengyichen.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 听雨 < 389625819@qq.com >
// +----------------------------------------------------------------------


namespace app\admin\controller;

use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\controller\Permissions;
class Task extends Permissions
{
    /**
     * 任务列表
     */
    public function index()
    {
        $model = Db::name('task');
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['goods_link'] = ['like', '%' . $post['keywords'] . '%'];
        }

        if (isset($post['status']) and ($post['status'] == 3 or $post['status'] == 2 or $post['status'] == 1 or $post['status'] === '0')) {
            $where['status'] = $post['status'];
        }

        if((isset($post['fprice']) and !empty($post['fprice'])) and (empty($post['lprice']))) {
            $where['price'] = ['>=',$post['fprice']];
        }elseif ((isset($post['lprice']) and !empty($post['lprice'])) and (empty($post['fprice']))){
            $where['price'] = ['<=',$post['lprice']];
        }elseif ((isset($post['fprice']) and !empty($post['fprice'])) and (isset($post['lprice']) and !empty($post['lprice']))){
            $where['price'] = [['>=',$post['fprice']],['<=',$post['lprice']]];
        }

        if(isset($post['add_time']) and !empty($post['add_time'])) {
            $min_time = strtotime($post['add_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['add_time'] = [['>=',$min_time],['<=',$max_time]];
        }

        $articles = empty($where) ? $model->order('add_time desc')->paginate(20) : $model->where($where)->order('add_time desc')->paginate(20,false,['query'=>$this->request->param()]);

        $article = $articles->toArray();

        foreach($article['data'] as $k => $v){
            //0未处理1已接受2拒绝3已支付
            if($v['status'] == 1){
                $article['data'][$k]['status_desc'] = '已接受';
            }elseif($v['status'] == 2){
                $article['data'][$k]['status_desc'] = '拒绝';
            }elseif($v['status'] == 3){
                $article['data'][$k]['status_desc'] = '已支付';
            }else{
                $article['data'][$k]['status_desc'] = '未处理';
            }
            $map['open_id'] = $v['open_id'];
            $article['data'][$k]['user'] = Db::name('user')->where($map)->find()['name'];
        }

        $this->assign('articles',$articles);
        $this->assign('data',$article['data']);
        return $this->fetch();
    }

    /**
     * 更改任务状态
     * @param id 任务ID
     */
    public function changeStatus()
    {
        $model = Db::name('task');
        $post = $this->request->param();
        $where['id'] = $post['id'];
        if(false == $model->where($where)->update($post,['status'=>$post['status']])) {
            return $this->error('修改失败');
        } else {
            return $this->success('修改成功','admin/task/index');
        }
    }

    /**
     * 任务订单列表
     */
    public function orderList()
    {
        $model = Db::name('task_order');
        $post = $this->request->param();
        if (isset($post['order_num']) and !empty($post['order_num'])) {
            $where['order_num'] = ['like', '%' . $post['order_num'] . '%'];
        }

        if (isset($post['goods_link']) and !empty($post['goods_link'])) {
            $where['goods_link'] = ['like', '%' . $post['goods_link'] . '%'];
        }

        if((isset($post['fprice']) and !empty($post['fprice'])) and (empty($post['lprice']))) {
            $where['price'] = ['>=',$post['fprice']];
        }elseif ((isset($post['lprice']) and !empty($post['lprice'])) and (empty($post['fprice']))){
            $where['price'] = ['<=',$post['lprice']];
        }elseif ((isset($post['fprice']) and !empty($post['fprice'])) and (isset($post['lprice']) and !empty($post['lprice']))){
            $where['price'] = [['>=',$post['fprice']],['<=',$post['lprice']]];
        }

        if (isset($post['user']) and !empty($post['user'])) {
            $where['user'] = ['like', '%' . $post['user'] . '%'];
        }

        if (isset($post['phone']) and !empty($post['phone'])) {
            $where['phone'] = ['like', '%' . $post['phone'] . '%'];
        }

        if (isset($post['address']) and !empty($post['address'])) {
            $where['address'] = ['like', '%' . $post['address'] . '%'];
        }

        if (isset($post['express_num']) and !empty($post['express_num'])) {
            $where['express_num'] = ['like', '%' . $post['express_num'] . '%'];
        }

        if(isset($post['add_time']) and !empty($post['add_time'])) {
            $min_time = strtotime($post['add_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['add_time'] = [['>=',$min_time],['<=',$max_time]];
        }
        $articles = empty($where) ? $model->order('add_time desc')->paginate(20) : $model->where($where)->order('add_time desc')->paginate(20,false,['query'=>$this->request->param()]);

        $article = $articles->toArray();

        $this->assign('articles',$articles);
        $this->assign('data',$article['data']);
        return $this->fetch();
    }


    /**
     * 修改任务订单
     * @param id 任务ID
     */
    public function publish()
    {
        //获取菜单id
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $model = Db::name('task_order');
        if($this->request->isPost()) {
            //是提交操作
            $post = $this->request->post();
            $where['id'] = $post['id'];
            if(false == $model->where($where)->update($post,['express_num'=>$post['express_num']])) {
                return $this->error('修改失败');
            } else {
                return $this->success('修改任务订单信息成功','admin/task/orderList');
            }
        } else {
            //非提交操作
            $data = $model->where('id',$id)->find();
            if(!empty($data)) {
                $this->assign('data',$data);
                return $this->fetch();
            } else {
                return $this->error('id不正确');
            }
        }
    }
}
