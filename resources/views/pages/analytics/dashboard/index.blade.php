@extends('layout.HUdefault')
@section('title', 'Dashboard')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <h1>{{ Lang::get('dashboard.title') }}</h1>

                <button type="button" id="graphBtn" style="display: none" class="btn btn-info" data-toggle="modal" data-target="#showDetails">+</button>

                <div id="showDetails" class="modal fade" role="dialog">
                    <div class="modal-dialog modal-lg">
                        <div id="GraphDetails" class="__reactRoot modal-content">
                            {{--Loading graph details page here--}}
                        </div>
                    </div>
                </div>

                @forelse($charts as $key => $chart)
                    <div class="col-sm-6">
                        <div class="panel panel-default">
                            <div class="panel-heading clearfix">
                                <div class="col-sm-9"><h3 class="panel-title">{{ $chart->chart->label }}</h3></div>
                                <div class="col-sm-3">
                                    <form action="{{ route('dashboard.move', [$chart->id, $chart->position, $chart->position - 1]) }}"
                                          style="display: inline-block;" method="post" accept-charset="UTF-8">
                                        {{ csrf_field() }}
                                        <button class="btn btn-default" title="Move up on the stack">&lsaquo;</button>
                                    </form>
                                    <form action="{{ route('dashboard.move', [$chart->id, $chart->position, $chart->position + 1]) }}"
                                          style="display: inline-block;" method="post" accept-charset="UTF-8">
                                        {{ csrf_field() }}
                                        <button class="btn btn-default" title="Move down on the stack">&rsaquo;</button>
                                    </form>
                                    <form action="{{ route('dashboard.delete', $chart->id) }}"

                                          style="display: inline-block;" method="post" accept-charset="UTF-8">
                                        {{ csrf_field() }}
                                        {{ method_field('delete') }}
                                        <button class="btn btn-danger" title="Remove">&times;</button>
                                    </form>
                                </div>
                            </div>
                            <div class="panel-body">
                                <canvas id="chart-{{ $key }}"></canvas>
                            </div>
                        </div>
                    </div>
                    @if ($chart === $charts->last())
                        <!--a href="{{ route('dashboard.add') }}" class="btn btn-primary" title="Add a chart">+</a-->
                    @endif
                @empty
                    <p>{{ Lang::get('dashboard.empty') }}</p>
                    <p>
                        <a href="{{ route('dashboard.add') }}"
                           class="btn btn-primary">{{ Lang::get('dashboard.add-chart') }}</a>
                    </p>
                @endforelse
            </div>
        </div>
    </div>
    <script>
        let lastColorIndex = 0;

        function getChartColor(reset = false) {
            if (reset) {
                lastColorIndex = 0;
            }
            const colors = [
                'rgba(255,99,132,1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
            ];
            if (lastColorIndex === colors.length) {
                lastColorIndex = 0;
            }
            return colors[lastColorIndex++];
        }

        (function () {
            $('.frmDelete').on('submit', function (e) {
                if (!confirm('{{ Lang::get('dashboard.warning') }}')) {
                    e.preventDefault();
                    return false
                }
            });
            @foreach($charts as $key => $dchart)<?php $chart = $dchart->chart ?>

            // The analysis id for the chart, need to find a way to use it inside graphClickEvent function.
            {{--console.log(JSON.parse('{!! json_encode($charts[$key]) !!}')['chart']['analysis_id']);--}}

            let ctx{{ $key }} = $('#chart-{{ $key }}');
            let chart{{ $key }} = new Chart(ctx{{  $key }}, {
                type: '{{ $chart->type->slug }}',
                data: {
                    labels: [<?php
                        $items = array_map(function ($k) use ($chart) {
                            return "'" . $k->{$chart->x_label->name} . "'";
                        }, $chart->analysis->data['data']);
                        echo join(', ', $items);
                        ?>],
                    datasets: [{
                        label: '{{ $chart->label }}',
                        backgroundColor: [

                            @foreach($chart->analysis->data['data'] as $c) {{-- For each data/column generate a color from a list --}}
                            @if($loop->first)
                            {{ "getChartColor(true),"}}
                            @else
                            {{ "getChartColor(),"}}
                            @endif

                            @endforeach
                        ],
                        // backgroundColor: defaultColours,
                        data: [<?php
                            $x_items = array_map(function ($k) use ($chart) {
                                return "'" . $k->{$chart->y_label->name} . "'";
                            }, $chart->analysis->data['data']);
                            echo join(', ', $x_items);
                            ?>]
                    }]
                },
                options: {
                    onClick: graphClickEvent,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
            @endforeach
        })
        ();

        function graphClickEvent(event, array) {
            let labels = JSON.parse('{!! json_encode($labels) !!}');

            if (array[0]) {
                let index = array[0]['_index'];
                if (index != null && index >= 0) {
                    let label = array[0]['_chart']['config']['data']['labels'][index];

                    // The label is a description
                    if ($.inArray(label, labels) >= 0) {
                        label = label.replace(/ /g, '_');

                        $('#GraphDetails').load('dashboard/chart_details/' + label, function () {
                            document.getElementById("graphBtn").click();
                        });
                    }
                }
            }
        }
    </script>

    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#addAnalysis" onclick="Wizard.open()">+</button>

    <div id="addAnalysis" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">

            <div id="QueryBuilder" class="modal-content"></div>

        </div>
    </div>
@stop