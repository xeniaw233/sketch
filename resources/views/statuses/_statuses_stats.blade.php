<li role="presentation" class="{{$active==1? 'active':''}}"><a href="{{ route('statuses.collections') }}">关注动态</a></li>
<li role="presentation" class="{{$active==0? 'active':''}}"><a href="{{ route('statuses.index') }}">全站动态</a></li>
<li role="presentation" class="pull-right {{$active==2? 'active':''}}"><a href="{{ route('users.index') }}">全部用户</a></li>
