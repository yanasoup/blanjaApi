<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller
{
    function __construct() {
        parent::__construct();
        #$this->load->model('mlogin');
        #$this->load->library('session');
    }
    public function index()
    {
        $tplVars['message'] = "";
        if (isset($_POST['btnlogin'])) {
            #die("XXX");
            $user = $this->mlogin->check($this->input->post())->result();
            #print_out($this->db->last_query());
            #print_out($user,0);
            if ($user) {
                if ($user[0]->is_active) {
                    $this->session->set_userdata('uid',$user[0]->id);
                    $this->session->set_userdata('uname',$user[0]->username);
                    redirect(base_url('/'));
                } else {
                    #print_out("USER INACTIVE");
                    $tplVars['message'] = "User inactive!";
                }
            } else {
                $tplVars['message'] = "Wrong credential!";
            }
        }

        $this->load->view('v_login',$tplVars);
    }

    public function logout() {
        $this->session->unset_userdata('uid');
        $this->session->unset_userdata('uname');
        redirect(base_url('/login'));
    }

    public function coba() {
        $tplVars['message'] = "";
        $this->load->view('v_login',$tplVars);
    }
}