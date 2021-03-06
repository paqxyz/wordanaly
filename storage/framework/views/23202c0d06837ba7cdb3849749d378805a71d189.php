<!DOCTYPE html>
<html lang="<?php echo e(config('app.locale')); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo e(Admin::title()); ?></title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <link rel="stylesheet" href="<?php echo e(asset("/packages/admin/AdminLTE/bootstrap/css/bootstrap.min.css")); ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo e(asset("/packages/admin/font-awesome/css/font-awesome.min.css")); ?>">

    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo e(asset("/packages/admin/AdminLTE/dist/css/skins/" . config('admin.skin') .".min.css")); ?>">

    <?php echo Admin::css(); ?>

    <link rel="stylesheet" href="<?php echo e(asset("/packages/admin/nestable/nestable.css")); ?>">
    <link rel="stylesheet" href="<?php echo e(asset("/packages/admin/toastr/build/toastr.min.css")); ?>">
    <link rel="stylesheet" href="<?php echo e(asset("/packages/admin/bootstrap3-editable/css/bootstrap-editable.css")); ?>">
    <link rel="stylesheet" href="<?php echo e(asset("/packages/admin/google-fonts/fonts.css")); ?>">
    <link rel="stylesheet" href="<?php echo e(asset("/packages/admin/AdminLTE/dist/css/AdminLTE.min.css")); ?>">

    <!-- REQUIRED JS SCRIPTS -->
    <script src="<?php echo e(asset ("/packages/admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js")); ?>"></script>
    <script src="<?php echo e(asset ("/packages/admin/AdminLTE/bootstrap/js/bootstrap.min.js")); ?>"></script>
    <script src="<?php echo e(asset ("/packages/admin/AdminLTE/plugins/slimScroll/jquery.slimscroll.min.js")); ?>"></script>
    <script src="<?php echo e(asset ("/packages/admin/AdminLTE/dist/js/app.min.js")); ?>"></script>
    <script src="<?php echo e(asset ("/packages/admin/jquery-pjax/jquery.pjax.js")); ?>"></script>

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body class="hold-transition <?php echo e(config('admin.skin')); ?> <?php echo e(join(' ', config('admin.layout'))); ?>">
<div class="wrapper">

    <?php echo $__env->make('admin::partials.header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

    <?php echo $__env->make('admin::partials.sidebar', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

    <div class="content-wrapper" id="pjax-container">
        <?php echo $__env->yieldContent('content'); ?>
        <?php echo Admin::script(); ?>

    </div>

    <?php echo $__env->make('admin::partials.footer', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

</div>

<!-- ./wrapper -->

<!-- REQUIRED JS SCRIPTS -->
<script src="<?php echo e(asset ("/packages/admin/AdminLTE/plugins/chartjs/Chart.min.js")); ?>"></script>
<script src="<?php echo e(asset ("/packages/admin/nestable/jquery.nestable.js")); ?>"></script>
<script src="<?php echo e(asset ("/packages/admin/toastr/build/toastr.min.js")); ?>"></script>
<script src="<?php echo e(asset ("/packages/admin/bootstrap3-editable/js/bootstrap-editable.min.js")); ?>"></script>

<?php echo Admin::js(); ?>


<script>

    function LA() {}
    LA.token = "<?php echo e(csrf_token()); ?>";

    $.fn.editable.defaults.params = function (params) {
        params._token = '<?php echo e(csrf_token()); ?>';
        params._editable = 1;
        params._method = 'PUT';
        return params;
    };

    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 4000
    };

    $.pjax.defaults.timeout = 5000;
    $.pjax.defaults.maxCacheLength = 0;
    $(document).pjax('a:not(a[target="_blank"])', {
        container: '#pjax-container'
    });

    $(document).on('submit', 'form[pjax-container]', function(event) {
        $.pjax.submit(event, '#pjax-container')
    });

    $(document).on("pjax:popstate", function() {

        $(document).one("pjax:end", function(event) {
            $(event.target).find("script[data-exec-on-popstate]").each(function() {
                $.globalEval(this.text || this.textContent || this.innerHTML || '');
            });
        });
    });
    
    $(document).on('pjax:send', function(xhr) {
        if(xhr.relatedTarget && xhr.relatedTarget.tagName && xhr.relatedTarget.tagName.toLowerCase() === 'form') {
            $submit_btn = $('form[pjax-container] :submit');
            if($submit_btn) {
                $submit_btn.button('loading')
            }
        }
    })
    
    $(document).on('pjax:complete', function(xhr) {
        if(xhr.relatedTarget && xhr.relatedTarget.tagName && xhr.relatedTarget.tagName.toLowerCase() === 'form') {
            $submit_btn = $('form[pjax-container] :submit');
            if($submit_btn) {
                $submit_btn.button('reset')
            }
        }
    })

    $(function(){
        $('.sidebar-menu li:not(.treeview) > a').on('click', function(){
            var $parent = $(this).parent().addClass('active');
            $parent.siblings('.treeview.active').find('> a').trigger('click');
            $parent.siblings().removeClass('active').find('li').removeClass('active');
        });
    });

</script>

</body>
</html>
