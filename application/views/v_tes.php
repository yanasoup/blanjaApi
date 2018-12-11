
<!-- page content -->
<div class="right_col" role="main">
    <div class="">

        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <h3>UPLOAD UPOINT PRODUCT CATALOG</h3>
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Upload</h2>
                        <ul class="nav navbar-right panel_toolbox" style="min-width:10px;">
                            <li><a class="collapse-link" id="show-filter-btn"><i class="fa fa-chevron-up"></i></a>
                            </li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content" style="display: block; color:#000;">
                        <br>
                        <form action="<?php echo base_url('catalog');?>" method="GET" class="form-inline form-label-right">
                            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2">
                                <div class="form-group">
                                    <label>File Excel :</label>
                                    <div class="input-group date tgl" name="dtf">
                                        <input name="filecatalog" type="file" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-xs-12 col-sm-9 col-md-9 col-lg-10">
                                    <button type="submit" class="btn btn-success">Submit</button>
                                </div>
                            </div>

                            <div class="ln_solid"></div>
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
                                <th>NO.</th>
                                <th>NCLI</th>
                                <th>NDOS</th>
                                <th>ND SPEEDY</th>
                                <th>TGL. PASANG / CABUT</th>
                                <th>NAMA PAKET</th>
                                <th>KODE WITEL</th>
                                <th>KAWASAN</th>
                                <th>STATUS</th>
                                <th>CREATED AT</th>
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
