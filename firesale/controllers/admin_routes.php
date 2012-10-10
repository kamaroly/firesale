<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Routes admin controller
 *
 * @author		Jamie Holdroyd
 * @author		Chris Harvey
 * @package		FireSale\Core\Controllers
 *
 */
class Admin_routes extends Admin_Controller
{

	public $section = 'routes';

	public function __construct()
	{

		parent::__construct();

		// Load libraries, drivers & models
		$this->load->driver('Streams');
		$this->load->model('routes_m');

		// Initialise data
		$this->data = new stdClass();

		// Get the stream
		$this->stream = $this->streams->streams->get_stream('firesale_routes', 'firesale_routes');

	}

	public function index()
	{

		// Variables
        $params = array(
            'stream'       => 'firesale_routes',
            'namespace'    => 'firesale_routes',
            'paginate'     => 'yes',
            'page_segment' => 4
        );

        // Assign routes
        $this->data->routes = $this->streams->entries->get_entries($params);

		// Add page data
		$this->template->title(lang('firesale:title') . ' ' . lang('firesale:sections:routes'))
					   ->set($this->data);

		// Fire events
		Events::trigger('page_build', $this->template);

		// Build page
		$this->template->build('admin/routes/index');

	}

	public function create()
	{

		// Variables
		$input = $this->input->post();
		$skip  = array('btnAction');
		$extra = array(
            'return'          => false,
            'success_message' => lang('firesale:routes:add_success'),
            'failure_message' => lang('firesale:routes:add_error'),
            'title'           => lang('firesale:routes:new')
        );

		// Build the form
		$fields = $this->fields->build_form($this->stream, 'new', $input, false, false, $skip, $extra);
		
		// Posted
		if( $this->input->post('btnAction') == 'save' OR $this->input->post('btnAction') == 'save_exit' )
		{

			// Got an ID back
			if( is_numeric($fields) )
			{
				// Add the route
				$this->routes_m->write($input['title'], $input['route'], $input['translation']);
			}

			// Redirect
			if( $input['btnAction'] == 'save_exit' OR ! is_numeric($fields) )
			{
				redirect('admin/firesale/routes');
			}
			else
			{
				redirect('admin/firesale/routes/edit/'.$fields);
			}

		}

		// Assign data
		$this->data->fields = $fields;

		// Build the page
        $this->template->title(lang('firesale:title').' '.lang('firesale:routes:new'))
        			   ->set($this->data)
        			   ->append_css('module::routes.css')
        			   ->append_js('module::jquery.caret.js')
					   ->append_js('module::routes.js')
        			   ->build('admin/routes/create');
	
	}

	public function edit($id)
	{

		// Variables
		$row   = $this->row_m->get_row($id, $this->stream, false);
		$input = $this->input->post();
		$skip  = array('btnAction');
		$extra = array(
            'return'          => false,
            'success_message' => lang('firesale:routes:edit_success'),
            'failure_message' => lang('firesale:routes:edit_error'),
            'title'           => lang('firesale:routes:edit')
        );

        // Not found
        if( empty($row) )
        {
        	$this->session->set_flashdata('error', lang('firesale:routes:not_found'));
        	redirect('admin/firesale/routes/create');
        }

        // Don't allow title and slug to be changed
        $_POST['title'] = $row->title;
        $_POST['slug']  = $row->slug;
        $_POST['table'] = $row->table;

		// Build the form
		$fields = $this->fields->build_form($this->stream, 'edit', $row, false, false, $skip, $extra);

		// Remove title and slug
		if( is_array($fields) )
		{
			// Remove title, slug and table
			unset($fields[0]);
			unset($fields[1]);
			unset($fields[2]);
		}
		
		// Posted
		if( $this->input->post('btnAction') == 'save' OR $this->input->post('btnAction') == 'save_exit' )
		{

			// Got an ID back
			if( is_numeric($fields) )
			{
				// Add the route
				$this->routes_m->write($row->title, $input['route'], $input['translation']);
			}

			// Redirect
			if( $input['btnAction'] == 'save_exit' OR ! is_numeric($fields) )
			{
				redirect('admin/firesale/routes');
			}
			else
			{
				redirect('admin/firesale/routes/edit/'.$id);
			}

		}

		// Assign data
		$this->data->row    = $row;
		$this->data->fields = $fields;

		// Build the page
        $this->template->title(lang('firesale:title').' '.sprintf(lang('firesale:routes:edit'), $row->title))
        			   ->set($this->data)
        			   ->append_css('module::routes.css')
        			   ->append_js('module::jquery.caret.js')
					   ->append_js('module::routes.js')
        			   ->build('admin/routes/edit');
	
	}

	public function rebuild()
	{

		// Variables
        $params = array(
            'stream'       => 'firesale_routes',
            'namespace'    => 'firesale_routes',
            'paginate'     => 'yes',
            'page_segment' => 4
        );

        // Get routes
        $routes = $this->streams->entries->get_entries($params);

        // Loop routes
        foreach( $routes['entries'] AS $route )
        {
        	// Rebuild
        	$this->routes_m->write($route['title'], $route['route'], $route['translation']);
        }

        // Flash and redirect
        $this->session->set_flashdata('success', lang('firesale:routes:build_success'));
        redirect('admin/firesale/routes');
	}

}