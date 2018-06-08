<?php

namespace App\Http\Controllers;

use App\Analysis\QueryBuilder\Builder;
use App\Analysis\QueryBuilder\Models;
use Illuminate\Http\Request;

class QueryBuilderController extends Controller
{

    public function showStep(Request $request, $id) {

        if($id == 0 && $request->isMethod('get')) {

            $request->session()->put('builder', []);
            $data = [];
            return view("pages.analytics.builder.step1-type", compact("data"));
        }

        $data = $request->session()->get('builder');

        if($data['analysis_type'] == 'build') {

            switch($id) {
                case 1: return view("pages.analytics.builder.step1-type", compact("data")); break;
                case 2: return $this->step2($data); break;
                case 3: return view("pages.analytics.builder.step3-builder-filters", compact("data")); break;
                case 4: return $this->step4($data); break;
            }
        } elseif($data['analysis_type'] == 'template') {

            switch($id) {
                case 1: return view("pages.analytics.builder.step1-type", compact("data")); break;
                case 2: return view("pages.analytics.builder.step2-template", compact("data")); break;
                case 4: return $this->step4($data); break;
            }
        } elseif($data['analysis_type'] == 'custom') {

            switch($id) {
                case 1: return view("pages.analytics.builder.step1-type", compact("data")); break;
                case 2: return view("pages.analytics.builder.step2-custom", compact("data")); break;
                case 4: return $this->step4($data); break;
            }
        }
    }

    public function saveStep(Request $request, $id) {

        if($request->isMethod('post')) {

            $request->session()->put('builder', array_replace_recursive($request->session()->get('builder'), $request->all()));
        }

        return json_encode(["step" => $id]);
    }

    private function step2($data) {

        $model = new Models();

        $models = $model->getAll();

        $relations = $model->getRelations('Cohort');

        return view("pages.analytics.builder.step2-builder", compact('models', 'relations', 'data'));
    }

    private function step4($data) {

        $table = $data['analysis_entity'];
        $relations = $data['analysis_relation'];
        $select = ['category.category_label', \DB::raw('SUM(LearningActivityProducing.duration)')];
        $filters = [['WorkplaceLearningPeriod.cohort_id', '=', '1']];
        $groupBy = ['category.category_label'];

        $result = (new Builder())->getQuery($table, $relations, $select, $filters, $groupBy);

        return view("pages.analytics.builder.step4-chart", compact("data", "result"));
    }

    public function getTables(Request $request) {
        $data = $request->session()->get('builder');

        $entities[] = $data['analysis_entity'];

        if(isset($data['analysis_relation'])) {

            foreach($data['analysis_relation'] as $relation) {

                $entities[] = $relation;
            }
        }

        return json_encode($entities);
    }

    public function getColumns($table) {
        return \DB::connection('dashboard')->getSchemaBuilder()->getColumnListing($table);
    }

    public function getRelations($modelString) {

        $model = new Models();

        $relations = $model->getRelations($modelString);

        return json_encode($relations);
    }

    public function executeQuery(Request $request) {

        $sessionData = $request->session()->get('builder');

        $table = (isset($sessionData['analysis_entity']) ? $sessionData['analysis_entity'] : []);
        $relations = (isset($sessionData['analysis_relation']) ? $sessionData['analysis_relation'] : []);

        $select = [];
        $filters = [];
        $groupBy = [];
        $limit = null;

        foreach($request->input('query_data') as $data) {

            switch($data['type']) {

                case "data":
                    $select[] = $data['table'].'.'.$data['column'];
                    break;

                case "sum":
                    $select[] = \DB::raw('SUM('.$data['table'].'.'.$data['column'].') as '.$data['column']);
                    break;

                case "count":
                    $select[] = \DB::raw('COUNT('.$data['table'].'.'.$data['column'].') as amount_of_'.$data['column']);
                    break;
            }
        }

        if($request->input('query_filter')) {

            foreach($request->input('query_filter') as $filter) {

                switch($filter['type']) {

                    case "limit":
                        $limit = $filter['value'];
                        break;

                    case "group":
                        $groupBy[] = $filter['table'].'.'.$filter['column'];
                        break;

                    case "equals":
                        $filters[] = [$filter['table'].'.'.$filter['column'], '=', $filter['value']];
                        break;
                }
            }
        }

        $result = (new Builder())->getData($table, $relations, $select, $filters, $groupBy);

        return json_encode($result);
    }
}