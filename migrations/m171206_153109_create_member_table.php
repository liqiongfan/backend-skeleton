<?php

use yii\db\Migration;

/**
 * 会员
 *
 * @author hiscaler <hiscaler@gmail.com>
 */
class m171206_153109_create_member_table extends Migration
{

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('{{%member}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull()->defaultValue(0)->comment('分类'),
            'type' => $this->smallInteger()->notNull()->defaultValue(0)->comment('会员类型'),
            'username' => $this->string(20)->notNull()->unique()->comment('帐号'),
            'nickname' => $this->string(60)->notNull()->comment('昵称'),
            'real_name' => $this->string(20)->comment('姓名'),
            'avatar' => $this->string(200)->comment('头像'),
            'auth_key' => $this->string(32)->notNull()->comment('认证 key'),
            'password_hash' => $this->string()->notNull()->comment('密码'),
            'password_reset_token' => $this->string()->unique()->comment('密码重置 token'),
            'access_token' => $this->string()->unique()->comment('访问 Token'),
            'email' => $this->string(50)->comment('邮箱'),
            'tel' => $this->string(30)->comment('电话号码'),
            'mobile_phone' => $this->string(35)->comment('手机号码'),
            'address' => $this->string(100)->comment('地址'),
            'register_ip' => $this->integer()->notNull()->comment('注册 IP'),
            'login_count' => $this->integer()->notNull()->defaultValue(0)->comment('登录次数'),
            'total_credits' => $this->integer()->notNull()->defaultValue(0)->comment('总积分'),
            'available_credits' => $this->integer()->notNull()->defaultValue(0)->comment('可用积分'),
            'last_login_ip' => $this->integer()->defaultValue(null)->comment('最后登录 IP'),
            'last_login_time' => $this->integer()->defaultValue(null)->comment('最后登录时间'),
            'status' => $this->smallInteger()->notNull()->defaultValue(0)->comment('状态'),
            'remark' => $this->text()->comment('备注'),
            'created_at' => $this->integer()->notNull()->comment('添加时间'),
            'created_by' => $this->integer()->notNull()->comment('添加人'),
            'updated_at' => $this->integer()->notNull()->comment('更新时间'),
            'updated_by' => $this->integer()->notNull()->comment('更新人'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('{{%member}}');
    }
}
