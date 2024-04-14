<!-- Main Header -->
<header class="main-header">

    <!-- Logo -->
    <a href="{{ admin_url('/') }}" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini">{!! config('admin.logo-mini', config('admin.name')) !!}</span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg">{!! config('admin.logo', config('admin.name')) !!}</span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
        <ul class="nav navbar-nav hidden-sm visible-lg-block">
        {!! Admin::getNavbar()->render('left') !!}
        </ul>

        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">

                {!! Admin::getNavbar()->render() !!}

                <!-- User Account Menu -->
                <li class="dropdown user user-menu">
                    <!-- Menu Toggle Button -->
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <!-- The user image in the navbar-->
                        <img src="{{ Admin::user()->avatar }}" class="user-image" alt="User Image">
                        <!-- hidden-xs hides the username on small devices so only the image appears. -->
                        <span class="hidden-xs">{{ Admin::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- The user image in the menu -->
                        <li class="user-header">
                            <img src="{{ Admin::user()->avatar }}" class="img-circle" alt="User Image">

                            <p>
                                {{ Admin::user()->name }}
                                <small>Member since admin {{ Admin::user()->created_at }}</small>
                            </p>
                        </li>
                        <li class="user-footer">
                            <div class="pull-left">
                                <a href="{{ admin_url('auth/setting') }}" class="btn btn-default btn-flat">{{ trans('admin.setting') }}</a>
                            </div>
                            <div class="pull-right">
                                <a href="{{ admin_url('auth/logout') }}" class="btn btn-default btn-flat">{{ trans('admin.logout') }}</a>
                            </div>
                        </li>
                    </ul>
                </li>
                <!-- Control Sidebar Toggle Button -->
                {{--<li>--}}
                    {{--<a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>--}}
                {{--</li>--}}
            </ul>
        </div>
    </nav>
    {{-- <script>
        function openConfirmationPopup() {
            alert("Please update hospital bed.");
        }
        window.onload = function() {
            openConfirmationPopup();
            setInterval(openConfirmationPopup, 60000);
        }

    </script> --}}

    <?php

$user = Auth::user();
$roles = $user->roles()->pluck('id')->first(); // Assuming you have defined a 'roles()' relationship in your User model


$userId = '1';

if ($roles === 1 || $roles === 2) {
    
// Function to get a random bed ID based on the authenticated user's login ID
function getRandomBedId() {
    $result = DB::table('beds')->select('beds.*')->orderBy('id', 'desc')->limit(1)->first();
    if ($result) {
        return $result->id;
    } else {
        return null;
    }
}

// Function to get the last updated date of a bed
function getRandomBedUpdate() {
    $result = DB::table('beds')->select('updated_at')->orderBy('id', 'desc')->limit(1)->first();
    if ($result) {
        return $result->updated_at;
    } else {
        return null;
    }
}

$bedId = getRandomBedId();
$updated_at = getRandomBedUpdate();

function isBedEmpty($bedId) {
    // Your logic to check if the bed is empty or not
    // For demonstration purposes, let's assume the bed is empty
    return true;
}

$isEmpty = isBedEmpty($bedId);

?>
<script>
function openConfirmationPopup(bedId, updated_at, isEmpty) {
    var message;
    if (isEmpty) {
        message = "The bed with ID " + bedId + " was last updated at " + updated_at + " and is empty.";
    } else {
        message = "Please confirm your action for bed ID: " + bedId + " last updated at " + updated_at + ".";
    }
    alert(message);
}

window.onload = function() {
    var bedId = <?php echo json_encode($bedId); ?>;
    var updated_at = <?php echo json_encode($updated_at); ?>;
    var isEmpty = <?php echo ($isEmpty ? 'true' : 'false'); ?>;
    
    openConfirmationPopup(bedId, updated_at, isEmpty);
    setInterval(function() {
        bedId = <?php echo json_encode(getRandomBedId()); ?>;
        updated_at = <?php echo json_encode(getRandomBedUpdate()); ?>;
        isEmpty = <?php echo (isBedEmpty($bedId) ? 'true' : 'false'); ?>;
        openConfirmationPopup(bedId, updated_at, isEmpty);
    }, 60000);
}
</script>
<?php } ?>
</header>