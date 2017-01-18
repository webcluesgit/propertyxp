<?php

class PropertiesSlider_Widget extends Aviators_Widget {
    function __construct() {
        parent::__construct(
            'PropertiesSlider_Widget',
            __('Aviators: Properties Slider', 'aviators'),
            array(
                'classname' => 'properties_slider_widget'
            )
        );
    }

    function widget($args, $instance) {
        global $wpdb;
        extract($args);
        $query_args['post_type'] = 'property';
        // property ids are set, ignore rest

        if (!$instance['property_ids']) {

            if(!isset($_SESSION["selected_cou_id1"]) && $_SESSION["selected_cou_id1"] == ""){

                // taxonomy contract types
                if (isset($instance['contract_types']) && $instance['contract_types']) {
                    $query_args['tax_query'][] = array(
                        'taxonomy' => 'contract_types',
                        'terms' => array_keys($instance['contract_types']),
                    );
                }

            // taxonomy types
            if (isset($instance['types']) && $instance['types']) {
                $query_args['tax_query'][] = array(
                    'taxonomy' => 'types',
                    'terms' => array_keys($instance['types']),
                );
            }

            // sort order
            if (!isset($instance['sort_order_default'])) {
                $query_args['orderby'] = $instance['sort_order_default'];
            }
            // sort criteria
            switch ($instance['sort_default']) {
                case 'title';
                    $query_args['orderby'] = 'title';
                    break;
                case 'created':
                    $query_args['orderby'] = 'date';
                    break;
                default:
                    // hydra fields!
                    $fieldModel = new HydraFieldModel();
                    $field = $fieldModel->load($instance['sort_default']);

                    if ($field) {
                        $machine_name = $field->field_name . '_0_value';
                        $query_args['orderby'] = 'meta_value_num';
                        $query_args['meta_key'] = $machine_name;
                    }

                    break;
            }

                if ($instance['limit'] > 0) {
                    $query_args['posts_per_page'] = $instance['limit'];
                }

                $query_args['meta_query'][] = array('key' => '_thumbnail_id');
                $slides = get_posts($query_args);

              //  echo "aaaa";die;

            }
            else{

               // echo "bbbb";die;


                if(isset($_SESSION["selected_city_id1"]) && $_SESSION["selected_city_id1"] != ""){
                    $query_args['meta_query'][] = array(
                        'key' => '_%_location',
                        'compare' => '=',
                        'value' => $_SESSION["selected_city_id1"]
                    );
                }

                if(isset($_SESSION["selected_cou_id1"]) && $_SESSION["selected_cou_id1"] != ""){

                    $query_args['meta_query'][] = array(
                        'key' => '_%_country',
                        'compare' => '=',
                        'value' => $_SESSION["selected_cou_id1"]
                    );

                }

                if(isset($_SESSION["selected_subloc_id1"]) && $_SESSION["selected_subloc_id1"] != ""){

                    $query_args['meta_query'][] = array(
                        'key' => '_%_sublocation',
                        'compare' => '=',
                        'value' => $_SESSION["selected_subloc_id1"]
                    );

                }
               // echo "cdfd";die;

                $slides = query_posts($query_args);


            }

        } else {

            $property_ids = explode(',', $instance['property_ids']);
            $property_ids = array_map('trim', $property_ids);
            $query_args['post__in'] = $property_ids;

        $query_args['meta_query'][] = array('key' => '_thumbnail_id');

            $slides = get_posts($query_args);
        }



      //  echo "<pre>";
      //  print_r($query_args);

//echo "DATA HERE";

      //  echo $wpdb->last_query;

     //   echo "<pre>";
     //   print_r($slides);

        if (!count($slides)) {
            return;
        }

        echo $before_widget;
        include 'templates/properties_slider.php';
        echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        $values = $_POST;
        if ($this->isSubmitted()) {
            $new_instance['property_ids'] = $values['property_ids'];
            $new_instance['contract_types'] = $values['contract_types'];
            $new_instance['types'] = $values['types'];
            $new_instance['sort_default'] = $values['sort_default'];
            $new_instance['sort_order_default'] = $values['sort_order_default'];
            $new_instance['limit'] = $values['limit'];

            // whoaau ?
            $new_instance['translations'] = $values['translations'];
        }

        return $new_instance;
    }

    function form($instance) {
        $form = new \Hydra\Builder($this->id_base, '', \Hydra\Builder::FORM_EXTENDER);
        $form->addField('hidden', array('widget_identifier', $this->id_base . '-' . $this->number));

        $form->addField('text', array('property_ids', __('Property IDs', 'aviators')))
            ->setDescription(__('Property IDs separated by comma. If set other filtering criteria will be ignored', 'aviators'));

        $terms = get_terms(array('types', 'contract_types'), array('get' => 'all'));

        $options = _aviators_properties_taxonomy_term_options('types', $terms);
        if ($options) {
            $form->addField('checkboxes', array('types', __('Filter by Type', 'aviators')))
                ->setOptions($options);
        }

        $options = _aviators_properties_taxonomy_term_options('contract_types', $terms);
        if ($options) {
            $form->addField('checkboxes', array('contract_types', __('Filter by Contract Type', 'aviators')))
                ->setOptions($options);
        }

        $sortableOptions = _aviators_settings_get_sortable_options();

        $form->addField('select', array('sort_default', __('Sort By', 'aviators')))
            ->setOptions($sortableOptions);

        $form->addField('select', array('sort_order_default', __('Default sort order by', 'aviators')))
            ->setOptions(array('ASC' => __('Ascending', 'aviators'), 'DESC' => __('Descending', 'aviators')));

        $form->addField('text', array('limit', __('Limit', 'aviators')))
            ->setDefaultValue(5)
            ->setDescription(__('Set -1 for unlimited', 'aviators'))
            ->addValidator('numeric');

        $form->setValues($instance);
        $form->render();
    }
}