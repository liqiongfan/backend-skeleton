/**
 * RBAC module javascript
 *
 * @author hiscaler <hiscaler@gmail.com>
 */
var yadjet = yadjet || {};
yadjet.rbac = yadjet.rbac || {};
yadjet.rbac.debug = yadjet.rbac.debug || true;
yadjet.rbac.urls = yadjet.rbac.urls || {
    assign: undefined,
    revoke: undefined,
    users: {
        list: undefined
    },
    user: {
        roles: undefined,
        permissions: undefined
    },
    roles: {
        list: undefined, // 角色列表
        create: undefined, // 添加角色
        read: undefined, // 查看角色
        update: undefined, // 更新角色
        'delete': undefined, // 删除角色
        permissions: undefined, // 角色对应的权限
        addChild: undefined, // 角色关联权限操作
        addChildren: undefined, // 添加所有权限至指定的角色
        removeChild: undefined, // 删除角色中的某个关联权限
        removeChildren: undefined, // 删除角色关联的所有权限
    },
    permissions: {
        create: undefined,
        read: undefined,
        update: undefined,
        'delete': undefined,
        scan: undefined
    }
};

axios.interceptors.request.use(function (config) {
    $.fn.lock();
    return config;
}, function (error) {
    $.fn.unlock();
    return Promise.reject(error);
});

axios.interceptors.response.use(function (response) {
    $.fn.unlock();
    return response;
}, function (error) {
    $.fn.unlock();
    return Promise.reject(error);
});

var vm = new Vue({
    el: '#rbac-app',
    data: {
        activeObject: {
            userId: 0,
            role: undefined
        },
        users: {
            items: {},
            extras: {}
        },
        user: {
            roles: {},
            permissions: {}
        },
        roles: [],
        role: {
            permissions: {}
        },
        permissions: [],
        pendingPermissions: {},
        formVisible: {
            role: false,
            permission: false
        }
    },
    methods: {
        isEmptyObject: function (e) {
            var t;
            for (t in e)
                return !1;
            return !0
        },
        userRolesByUserId: function (userId, index) {
            axios.get(yadjet.rbac.urls.user.roles.replace('_id', userId))
                .then(function (response) {
                    vm.user.roles = response.data;
                    vm.activeObject.userId = userId;
                    var $tr = $('#rbac-users > table tr:eq(' + (index + 1) + ')');
                    var offset = $tr.offset();
                    $('#rbac-pop-window').css({
                        position: 'absolute',
                        left: offset.left + 40,
                        top: offset.top + $tr.find('td').outerHeight()
                    });
                })
                .catch(function (error) {
                    vm.user.roles = [];
                    vm.activeObject.userId = undefined;
                });
        },
        // 给用户授权
        assign: function (roleName, index) {
            axios.post(yadjet.rbac.urls.assign, {roleName: roleName, userId: vm.activeObject.userId})
                .then(function (response) {
                    vm.user.roles.push(vm.roles[index]);
                })
                .catch(function (error) {
                });
        },
        // 撤销用户授权
        revoke: function (roleName, index) {
            axios.post(yadjet.rbac.urls.revoke, {roleName: roleName, userId: vm.activeObject.userId})
                .then(function (response) {
                    for (var i in vm.user.roles) {
                        console.info(vm.user.roles[i].name);
                        if (vm.user.roles[i].name === roleName) {
                            vm.user.roles.splice(i, 1);
                            break;
                        }
                    }
                })
                .catch(function (error) {
                });
        },
        // 删除角色
        roleDelete: function (roleName, index, event) {
            layer.confirm('确定删除该角色？', {icon: 3, title: '提示'}, function (boxIndex) {
                axios.post(yadjet.rbac.urls.roles.delete.replace('_name', roleName))
                    .then(function (response) {
                        vm.roles.splice(index, 1);
                    })
                    .catch(function (error) {
                    });
                
                layer.close(boxIndex);
            });
        },
        // 删除角色关联的所有权限
        roleRemoveChildren: function (roleName) {
            layer.confirm('删除该角色关联的所有权限？', {icon: 3, title: '提示'}, function (boxIndex) {
                axios.post(yadjet.rbac.urls.roles.removeChildren.replace('_name', roleName))
                    .then(function (response) {
                        vm.role.permissions = [];
                    })
                    .catch(function (error) {
                    });
                
                layer.close(boxIndex);
            });
        },
        // 根据角色获取关联的所有权限
        permissionsByRole: function (roleName, index) {
            axios.get(yadjet.rbac.urls.roles.permissions.replace('_roleName', roleName))
                .then(function (response) {
                    vm.activeObject.role = roleName;
                    vm.role.permissions = response.data;
                })
                .catch(function (error) {
                });
        },
        // 分配权限给角色
        roleAddChild: function (permissionName, index, event) {
            axios.post(yadjet.rbac.urls.roles.addChild.replace('_roleName', vm.activeObject.role).replace('_permissionName', permissionName))
                .then(function (response) {
                    for (var i in vm.permissions) {
                        if (vm.permissions[i].name == permissionName) {
                            vm.role.permissions.push(vm.permissions[i]);
                            break;
                        }
                    }
                })
                .catch(function (error) {
                });
        },
        // 添加所有权限至指定的角色
        roleAddChildren: function (index, event) {
            axios.post(yadjet.rbac.urls.roles.addChildren.replace('_roleName', vm.activeObject.role))
                .then(function (response) {
                    for (var i in vm.permissions) {
                        vm.role.permissions.push(vm.permissions[i]);
                    }
                })
                .catch(function (error) {
                });
        },
        // 从角色中移除权限
        roleRemoveChild: function (permissionName, index, event) {
            layer.confirm('确定删除该权限？', {icon: 3, title: '提示'}, function (boxIndex) {
                axios.post(yadjet.rbac.urls.roles.removeChild.replace('_roleName', vm.activeObject.role).replace('_permissionName', permissionName))
                    .then(function (response) {
                        for (var i in vm.role.permissions) {
                            if (vm.role.permissions[i].name == permissionName) {
                                vm.role.permissions.splice(i, 1);
                                break;
                            }
                        }
                    })
                    .catch(function (error) {
                    });
                
                layer.close(boxIndex);
            });
        },
        // 切换添加表单是否可见
        toggleFormVisible: function (formName) {
            vm.formVisible[formName] = !vm.formVisible[formName];
        },
        // 保存扫描的权限
        permissionSave: function (name, description, index, event) {
            axios.post(yadjet.rbac.urls.permissions.create, {name: name, description: description})
                .then(function (response) {
                    if (response.data.success) {
                        vm.permissions.push(response.data.data);
                        vm.pendingPermissions[index].active = false;
                    }
                })
                .catch(function (error) {
                });
        },
        // 删除单个权限
        permissionDelete: function (name, index, event) {
            layer.confirm('确定删除该权限？', {icon: 3, title: '提示'}, function (boxIndex) {
                axios.post(yadjet.rbac.urls.permissions.delete.replace('_name', name))
                    .then(function (response) {
                        vm.permissions.splice(index, 1);
                        for (var i in vm.pendingPermissions) {
                            if (vm.pendingPermissions[i].name == name) {
                                vm.pendingPermissions[i].active = true;
                                break;
                            }
                        }
                    })
                    .catch(function (error) {
                    });
                
                layer.close(boxIndex);
            });
        }
    },
    computed: {
        // 当前用户的角色
        userRoles: function () {
            var roles = [], role;
            for (var i in this.roles) {
                role = clone(this.roles[i]);
                role.active = false;
                for (var j in vm.user.roles) {
                    if (role.name == this.user.roles[j].name) {
                        role.active = true;
                        break;
                    }
                }
                roles.push(role);
            }
            
            return roles;
        },
        // 当前操作角色关联的权限
        rolePermissions: function () {
            var permissions = [], permission;
            for (var i in this.permissions) {
                permission = clone(this.permissions[i]);
                permission.active = false;
                for (var j in this.role.permissions) {
                    if (permission.name == this.role.permissions[j].name) {
                        permission.active = true;
                        break;
                    }
                }
                permissions.push(permission);
            }
            
            return permissions;
        }
    }
});

$(function () {
    $('.rbac-tabs-common li a').on('click', function () {
        var $t = $(this);
        $t.parent().addClass('active').siblings().removeClass('active');
        $('#rbac-app .panel').hide();
        $('#rbac-app #' + $t.attr('data-toggle')).show();
        
        return false;
    });
    
    $('#rbac-sumbit-role').on('click', function () {
        $.ajax({
            type: 'POST',
            url: yadjet.rbac.urls.roles.create,
            data: $('#rbac-role-form form').serialize(),
            returnType: 'json',
            success: function (response) {
                if (response.success) {
                    // vm.roles[response.data.name] = response.data;
                    vm.roles.push(response.data);
                } else {
                    layer.alert(response.error.message);
                }
            }, error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.alert('ERROR ' + XMLHttpRequest.status + ' 错误信息： ' + XMLHttpRequest.responseText);
            }
        });
        
        return false;
    });
    
    $('#rbac-sumbit-permission').on('click', function () {
        $.ajax({
            type: 'POST',
            url: yadjet.rbac.urls.permissions.create,
            data: $('#rbac-persmission-form form').serialize(),
            returnType: 'json',
            success: function (response) {
                if (response.success) {
                    vm.permissions.push(response.data);
                } else {
                    layer.alert(response.error.message);
                }
            }, error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.alert('ERROR ' + XMLHttpRequest.status + ' 错误信息： ' + XMLHttpRequest.responseText);
            }
        });
        
        return false;
    });
});