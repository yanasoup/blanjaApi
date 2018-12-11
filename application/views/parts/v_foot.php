
<!-- footer content -->
<footer>
    <div class="pull-right">
        UPoint-Blanja API Dashboard
    </div>
    <div class="clearfix"></div>
</footer>
<!-- /footer content -->
</div>
</div>

<script>
    var BASE_URL = "<?php echo base_url();?>";
    var ROW_OFFSET = parseInt("<?php echo $page;?>");
    console.log('ROW_OFFSET',ROW_OFFSET);
</script>
<!-- jQuery -->
<script src="<?php echo base_url('assets/vendors/jquery/dist/jquery.min.js');?>"></script>
<!-- Bootstrap -->
<script src="<?php echo base_url('assets/vendors/bootstrap/dist/js/bootstrap.min.js');?>"></script>
<!-- FastClick -->
<script src="<?php echo base_url('assets/vendors/fastclick/lib/fastclick.js');?>"></script>
<!-- NProgress -->
<script src="<?php echo base_url('assets/vendors/nprogress/nprogress.js');?>"></script>
<!-- iCheck -->
<script src="<?php echo base_url('assets/vendors/iCheck/icheck.min.js');?>"></script>

<!-- bootstrap-daterangepicker -->
<script src="<?php echo base_url('assets/vendors/moment/min/moment.min.js');?>"></script>
<script src="<?php echo base_url('assets/vendors/bootstrap-daterangepicker/daterangepicker.js');?>"></script>
<!-- bootstrap-datetimepicker -->
<script src="<?php echo base_url('assets/vendors/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js');?>"></script>


<!-- Custom Theme Scripts -->
<script src="<?php echo base_url('assets/build/js/hsi-gamer.js');?>"></script>

</body>
</html>