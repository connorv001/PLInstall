{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    Plugins
@endsection

@section('content-header')
    <h1>Plugins</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>@lang('navigation.server.configuration')</li>
        <li class="active">Plugins</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">All Plugins</h3>
                    <div class="box-tools search01">
                        <form id="search_form">
                            <div class="form-group-sm pull-left">
                                <select id="search_size" name="search_size" class="form-control">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="text" name="search_query" id="search_query" class="form-control pull-right"
                                       value="" placeholder="Search">
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-default" id="search_button"><i
                                            class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="paginate"></div>
    
    <div class="row" id="plugin_list">
        <div class="col-xs-12">
            <div class="text-center" style="background-color: lightgrey; padding-top: 3rem; padding-bottom: 3rem;">
                <i class="fa fa-search fa-4x"></i>
                <br><br>
                <p class="text-bold">Search for plugins in <a href="https://www.spigotmc.org/" target="_blank">SpigotMC.org</a>
                </p>
            </div>
        </div>
    </div>

    <div class="row" id="paginate"></div>

@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    <script>
        $("#search_form").submit(function (event) {
            event.preventDefault();
            search();
        });

        let installedPlugins = JSON.parse('{!! $installedPlugins !!}');

        let thisPage = 1;

        function convertTimestamp(timestamp) {
            var d = new Date(timestamp * 1000), // Convert the passed timestamp to milliseconds
                yyyy = d.getFullYear(),
                mm = ('0' + (d.getMonth() + 1)).slice(-2),  // Months are zero based. Add leading 0.
                dd = ('0' + d.getDate()).slice(-2),         // Add leading 0.
                hh = d.getHours(),
                h = hh,
                min = ('0' + d.getMinutes()).slice(-2),     // Add leading 0.
                ampm = 'AM',
                time;
        
            if (hh > 12) {
                h = hh - 12;
                ampm = 'PM';
            } else if (hh === 12) {
                h = 12;
                ampm = 'PM';
            } else if (hh == 0) {
                h = 12;
            }
        
            time = yyyy + '-' + mm + '-' + dd + ', ' + h + ':' + min + ' ' + ampm;
            return time;
        }

        function search(page = 1) {
            $.ajax({
                method: 'POST',
                url: '/server/{{$server->uuidShort}}/plugins/search',
                headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                data: {
                    query: $("#search_query").val(),
                    size: $("#search_size").val(),
                    page: page
                }
            }).done(function (data) {
                if (data.success === true) {
                    $('#plugin_list').html('');

                    $.each(data.response, function (key, item) {
                        if (item['file']['type'] == '.jar') {
                            let stars = '';
                            item['rating']['average'] >= 1 ? stars += '<i class="fa fa-star" style="color: #F3CB06;"></i>' : stars += '<i class="fa fa-star-o"></i>';
                            item['rating']['average'] >= 2 ? stars += '<i class="fa fa-star" style="color: #F3CB06;"></i>' : stars += '<i class="fa fa-star-o"></i>';
                            item['rating']['average'] >= 3 ? stars += '<i class="fa fa-star" style="color: #F3CB06;"></i>' : stars += '<i class="fa fa-star-o"></i>';
                            item['rating']['average'] >= 4 ? stars += '<i class="fa fa-star" style="color: #F3CB06;"></i>' : stars += '<i class="fa fa-star-o"></i>';
                            item['rating']['average'] >= 5 ? stars += '<i class="fa fa-star" style="color: #F3CB06;"></i>' : stars += '<i class="fa fa-star-o"></i>';

                            let rimg = '';
                            if (item['icon']['url'] == "") {
                                rimg += '<img src="https://static.spigotmc.org/styles/spigot/xenresource/resource_icon.png" class="avatar rounded-circle mr-3">';
                            } else {
                                rimg += '<img src="data:image/png;base64, ' + item['icon']['data'] + '" class="avatar rounded-circle mr-3" />';
                            }
                            var releaseDate = convertTimestamp(item['releaseDate']);
                            var updateDate = convertTimestamp(item['updateDate']);

                            let dl = '<button id="dl-' + item['id'] + '" class="btn btn-success btn-sm pull-right" onclick="downloadPlugin(' + item['id'] + ');"><i class="fa fa-download"></i> Download</button>';
                            $.each(installedPlugins, function (plugin_key, plugin) {
                                if (plugin['plugin_id'] == item['id']) {
                                    dl = '<button id="dl-' + item['id'] + '" class="btn btn-danger btn-sm pull-right" onclick="removePlugin(' + item['id'] + ');"><i class="fa fa-trash"></i> Remove</button>';
                                }
                            });

                            $('#plugin_list').append(
                                '<div id="spigot">' +
                                '   <div class="col-xs-12 col-sm-4">' +
                                '       <div class="box">' +
                                '           <div class="box-header">' +
                                rimg +
                                '               <div class="box-tools">' +
                                '                   Rating: ' + stars + ' (' + item['rating']['average'] + '/5)' +
                                '               </div>' +
                                '               <div class="box-tools2" style="position: absolute; right: 10px; top: 30px;">' +
                                '                   Released: ' + releaseDate +
                                '               </div>' +
                                '               <div class="box-tools3" style="position: absolute; right: 10px; top: 55px;">' +
                                '                   Updated: ' + updateDate + 
                                '               </div>' +
                                '           </div>' +
                                '           <div class="box-body">' +
                                '               <h3><a href="https://api.spiget.org/v2/resources/' + item['id'] + '/go" target="_blank">' + item['name'] + '</a></h3><br/>' + 
                                '               Tested Versions: (' + item['testedVersions'] + ') <br/>' +
                                                item['tag'] + 
                                                dl +
                                '           </div>' +
                                '       </div>' +
                                '   </div>' +
                                '</div>'
                            );
                        }
                    });

                    thisPage = data.page;
                    let startPage = data.page - 3;

                    let pages = '';
                    for (let i = startPage; i < startPage + 7; i++) {
                        if (startPage < 1) {
                            startPage += 1;
                        } else {
                            if (data.page == i) {
                                pages += '<li class="page-item active"><a class="page-link" href="#" onclick="searchWithPage(' + i + ')">' + i + '</a></li>';
                            } else {
                                pages += '<li class="page-item"><a class="page-link" href="#" onclick="searchWithPage(' + i + ')">' + i + '</a></li>';
                            }
                        }
                    }

                    $('#paginate').html(
                        '<div class="col-xs-12 text-center">' +
                        '<nav aria-label="Page navigation example">' +
                        '  <ul class="pagination">' +
                        '    <li class="page-item"><a class="page-link" href="javascript:;" onclick="prew();"><i class="fa fa-angle-left"></i></a></li>' +
                        pages +
                        '    <li class="page-item"><a class="page-link" href="javascript:;" onclick="next();"><i class="fa fa-angle-right"></i></a></li>' +
                        '  </ul>' +
                        '</nav>' +
                        '</div>'
                    );
                } else {
                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: (typeof data.error !== 'undefined') ? data.error : 'Couldn\'t search! Please try again later...'
                    });
                }
            }).fail(function (jqXHR) {
                swal({
                    type: 'error',
                    title: 'Ooops!',
                    text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'A system error has occurred! Please try again later...'
                });
                console.log(jqXHR.responseText);
            });
        }

        function searchWithPage(page) {
            search(page);
        }

        function next() {
            search(parseInt(thisPage) + 1);
        }

        function prew() {
            search(parseInt(thisPage) - 1);
        }

        function downloadPlugin(id) {
            $('#dl-' + id).html('<i class=\'fa fa-spinner fa-spin \'></i> Downloading...');

            $.ajax({
                method: 'POST',
                url: '/server/{{$server->uuidShort}}/plugins/download',
                headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                data: {
                    id: id
                }
            }).done(function (data) {
                if (data.success === true) {
                    $('#dl-' + id).html('<i class="fa fa-trash"></i> Remove').removeAttr('onclick').addClass('btn-danger').removeClass('btn-success').attr('onclick', 'removePlugin(' + id + ');');

                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'Successful plugin installation!'
                    });
                } else {
                    $('#dl-' + id).html(' <i class="fa fa-download"></i> Download');

                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: (typeof data.error !== 'undefined') ? data.error : 'Couldn\'t search! Please try again later...'
                    });
                }
            }).fail(function (jqXHR) {
                $('#dl-' + id).html('<i class="fa fa-download"></i> Download');

                swal({
                    type: 'error',
                    title: 'Ooops!',
                    text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'A system error has occurred! Please try again later...'
                });
                console.log(jqXHR.responseText);
            });
        }

        function removePlugin(id) {
            $('#dl-' + id).html('<i class=\'fa fa-spinner fa-spin \'></i> Removing...');

            $.ajax({
                method: 'POST',
                url: '/server/{{$server->uuidShort}}/plugins/remove',
                headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                data: {
                    id: id
                }
            }).done(function (data) {
                if (data.success === true) {
                    $('#dl-' + id).html('<i class="fa fa-download"></i> Download').removeAttr('onclick').addClass('btn-success').removeClass('btn-danger').attr('onclick', 'downloadPlugin(' + id + ');');

                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'Successful plugin deletion!'
                    });
                } else {
                    $('#dl-' + id).html(' <i class="fa fa-trash"></i> Remove');

                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: (typeof data.error !== 'undefined') ? data.error : 'Couldn\'t search! Please try again later...'
                    });
                }
            }).fail(function (jqXHR) {
                $('#dl-' + id).html('<i class="fa fa-trash"></i> Remove');

                swal({
                    type: 'error',
                    title: 'Ooops!',
                    text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'A system error has occurred! Please try again later...'
                });
                console.log(jqXHR.responseText);
            });
        }
    </script>
@endsection
