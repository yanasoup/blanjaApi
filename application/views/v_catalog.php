
<!-- page content -->
<div class="right_col" role="main">
    <div class="">

        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <h3>PRODUCTS</h3>
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Upload Products Catalog</h2>
                        <ul class="nav navbar-right panel_toolbox" style="min-width:10px;">
                            <li><a class="collapse-link" id="show-filter-btn"><i class="fa fa-chevron-up"></i></a>
                            </li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content" style="display: block; color:#000;">
                        <br>
                        <form method="post" action="<?php echo base_url('catalog');?>" enctype="multipart/form-data" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="">

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">File Excel <span class="required">*</span>
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input type="file" name="file_excel" required="required" class="form-control col-md-7 col-xs-12">
                                </div>
                            </div>
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <button type="submit" name="btnsubmit" value="submit" class="btn btn-success">Submit</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
                <div class="x_panel" style="color:#000;">
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                        </div>
                    </div>
                    <div class="x_content table-responsive" style="display: block;">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>No.</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>List Price</th>
                                <th>SKU outerCode</th>
                                <th>SKU Qty.</th>
                                <th>SKU Price</th>
                                <th>Image URL</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">

                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
<!-- /page content -->
