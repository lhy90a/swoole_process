# swoole_process
swoole process manage api

#### 开启进程服务
url: http://127.0.0.1:9595/?action=start&service=Test1Service


#### 关闭进程服务
url: http://127.0.0.1:9595/?action=stop&service=Test1Service


#### 强制关闭进程服务
url: http://127.0.0.1:9595/?action=force&service=Test1Service


#### 结束所有进程服务
url: http://127.0.0.1:9595/?action=stopall&service=Test1Service


#### 主进程状态
url: http://127.0.0.1:9595/?action=status&service=Test1Service


#### 服务列表及状态
url: http://127.0.0.1:9595/?action=list&service=Test1Service


#### 服务列表
url: http://127.0.0.1:9595/?action=list_service&service=Test1Service


#### 临时增加服务
url: http://127.0.0.1:9595/?action=add_service&service=Test1Service