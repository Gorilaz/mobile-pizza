<?php
/**
 * Created by PhpStorm.
 * User: GabrielCol
 * Date: 11/9/13
 * Time: 3:45 PM
 */

class General extends CI_Model {

    const PAGE_ABOUT            = 6;
    const PAGE_ORDER_SUCCESS    = 1;
    const PAGE_ORDER_FAILED     = 2;
    const PAGE_404              = 5;

    /**
     * Check if the restaurant is open now
     * @return bool
     */
    public function isOpenNow() {

        /* $time = date('h:iA');

        if (strstr($time, 'PM'))
            $time = date("H:i", strtotime($time));
        else if (strstr($time, 'AM'))
            $time = date("h:i", strtotime($time)); */

        $day = strtolower(date('l'));
        $time = date('H:i');

        $dateRange = '( ( UNIX_TIMESTAMP( CONCAT( DATE( NOW() ), \' \', \'' . $time . '\' ) ) BETWEEN UNIX_TIMESTAMP( CONCAT( DATE( NOW() ), \' \', `first_half_fr` ) ) AND UNIX_TIMESTAMP( CONCAT( DATE( NOW() ), \' \', `first_half_t` ) ) ) OR ( UNIX_TIMESTAMP( CONCAT( DATE( NOW() ), \' \', \'' . $time . '\' ) ) BETWEEN UNIX_TIMESTAMP( CONCAT( DATE( NOW() ), \' \', `second_half_fr` ) ) AND UNIX_TIMESTAMP( CONCAT( DATE( NOW() ), \' \', `second_half_t` ) ) ) )';

        $row = $this->db->where(array('day' => $day))->where($dateRange . ' IS TRUE', '', FALSE)->get('tbl_shop_timings')->num_rows();

        return $row > 0;
    }

    public function weWillOpen()
    {
        $schedule = array();

        $entries = $this->db->get('tbl_shop_timings')->result();

        $time_is_over = array();

        foreach( $entries as $entry )
        {
            $weekday = date('w', strtotime($entry->day));

            if( $entry->first_half_from != NULL && 
                $entry->second_half_from != NULL )
            {
                $schedule[$entry->day][$entry->timing_for] = $this->timesForSchedule($entry);

                if( date('w') === $weekday )
                {
                    $time_is_over[$entry->timing_for] = $schedule[$entry->day][$entry->timing_for]['time_is_over'];
                }

                unset($schedule[$entry->day][$entry->timing_for]['time_is_over']);
            }
        }

        if( !empty($time_is_over['D']) && !empty($time_is_over['P']) )
        {
            $current_timestamp = time();
            $current_weekday = date('l', $current_timestamp);

            $date_timestamp = $current_timestamp;

            do
            {
                $date_timestamp = strtotime(date('Y-m-d', $date_timestamp) . ' + 1 day');

                $date_weekday = strtolower(date('l', $date_timestamp));

                if( isset($schedule[$date_weekday]) )
                {
                    if( !empty($schedule[$date_weekday]['D']) || !empty($schedule[$date_weekday]['P']) )
                    {
                        reset($schedule[$date_weekday]['D']);
                        reset($schedule[$date_weekday]['P']);

                        $date_start_D = key($schedule[$date_weekday]['D']);
                        $date_start_P = key($schedule[$date_weekday]['P']);

                        if( empty($schedule[$date_weekday]['D']) )
                        {
                            return date('Y-m-d', $date_timestamp) . ' ' . $date_start_P;
                        }

                        if( empty($schedule[$date_weekday]['P']) )
                        {
                            return date('Y-m-d', $date_timestamp) . ' ' . $date_start_D;
                        }

                        if( strtotime(date('Y-m-d', $date_timestamp) . ' ' . $date_start_D) < strtotime(date('Y-m-d', $date_timestamp) . ' ' . $date_start_P) )
                        {
                            return date('Y-m-d', $date_timestamp) . ' ' . $date_start_D;
                        }

                        return date('Y-m-d', $date_timestamp) . ' ' . $date_start_P;
                    }
                }
            }
            while( $date_weekday !== $current_weekday );
        }
        else
        {
            $weekday = strtolower(date('l'));

            reset($schedule[$weekday]['D']);
            reset($schedule[$weekday]['P']);

            $start_D = key($schedule[$weekday]['D']);
            $start_P = key($schedule[$weekday]['P']);

            if( !empty($time_is_over['D']) )
            {
                return date('Y-m-d') . ' ' . $start_P;
            }

            if( !empty($time_is_over['P']) )
            {
                return date('Y-m-d') . ' ' . $start_D;
            }

            if( strtotime($start_D) < strtotime($start_P) )
            {
                return date('Y-m-d') . ' ' . $start_D;
            }

            return date('Y-m-d') . ' ' . $start_P;
        }
    }

    public function getSiteText($item) {

        if(!is_array($item)) {
            $select[] = $item;
        } else {
            $select = $item;
        }

        $this->db->select('type, value');
        $this->db->from('tbl_manage_text');
        $this->db->where_in('type',$select);
        $query = $this->db->get();

        if(!is_array($item)) {
            return $query->row();
        } else {
            foreach( $query->result() as $entry) {
                $return[$entry->type] = $entry->value;
            }
            return $return;
        }
    }

    /**
     * Get shop delivery Hours
     * @return array
     */
    public function shopSchedule() {
        $forTwig = array();
        $forJquery = array();

        $time_is_over = array();

        $forJquery[date('Y-m-d')] = array('D' => array(), 'P' => array());

        $entries = $this->db->get('tbl_shop_timings')->result();

        foreach( $entries as $entry )
        {
            $weekday = date('w', strtotime($entry->day));

            if( $entry->first_half_from != NULL && 
                $entry->second_half_from != NULL && 
                date('w') === $weekday )
            {
                $forTwig[$weekday]['name'] = date('F jS', strtotime($entry->day));
                $forTwig[$weekday]['value'] = date('Y-m-d', strtotime($entry->day));

                $forJquery[date('Y-m-d', strtotime($entry->day))][$entry->timing_for] = $this->formatTimesForSchedule($entry);

                if( isset($forJquery[date('Y-m-d', strtotime($entry->day))][$entry->timing_for]['start_time']) )
                {
                    $start_time = $forJquery[date('Y-m-d', strtotime($entry->day))][$entry->timing_for]['start_time'];

                    unset($forJquery[date('Y-m-d', strtotime($entry->day))][$entry->timing_for]['start_time']);
                }

                $time_is_over[$entry->timing_for] = $forJquery[date('Y-m-d', strtotime($entry->day))][$entry->timing_for]['time_is_over'];

                unset($forJquery[date('Y-m-d', strtotime($entry->day))][$entry->timing_for]['time_is_over']);
            }
        }

        /* Sort array starting with today */
        $this->aasort($forTwig, 'value');

        $times = array_keys($forJquery[date('Y-m-d')]['P']);

        $start_time = isset($start_time) ? $start_time : false;

        return array('forTwig' => $forTwig, 'forJquery' => $forJquery, 'start_time' => $start_time, 'time_is_over' => $time_is_over);
    }

    /**
     * Helper to sort date array
     * @param $array
     * @param $key
     */
    private function aasort (&$array, $key) {
        $sorter=array();
        $ret=array();
        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii]=$va[$key];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) {
            $ret[$ii]=$array[$ii];
        }
        $array=$ret;
    }
    
    /**
     * Helper for hours
     * @param $row
     * @return array|bool
     */
    private function timesForSchedule($row)
    {
        $time = array();

        $start = strtotime($row->first_half_from);
        $end = strtotime($row->first_half_to);

        $time_is_over = true;

        if( $row->timing_for === 'D' )
        {
            $delivery_time = $this->db->where('type', 'delivery_time')->get('sitesetting')->row()->value;

            $start += (integer) $delivery_time * 60;
        }

        if( (integer) date('i', $start) === 0 )
        {
            $start = strtotime(date('G', $start) . ':00');
        }
        else if( ( (integer) date('i', $start) > 0 ) && 
            ( (integer) date('i', $start) <= 15 ) )
        {
            $start = strtotime(date('G', $start) . ':15');
        }
        else if( ( (integer) date('i', $start) > 15 ) && 
            ( (integer) date('i', $start) <= 30 ) )
        {
            $start = strtotime(date('G', $start) . ':30');
        }
        else if( ( (integer) date('i', $start) > 30 ) && 
            ( (integer) date('i', $start) <= 45 ) )
        {
            $start = strtotime(date('G', $start) . ':45');
        }
        else
        {
            $h = date('G', $start);

            $h++;

            $start = strtotime($h . ':00');
        }

        if( (integer) date('i', $end) === 0 )
        {
            $end = strtotime(date('G', $end) . ':00');
        }
        else if( ( (integer) date('i', $end) > 0 ) && 
            ( (integer) date('i', $end) <= 15 ) )
        {
            $end = strtotime(date('G', $end) . ':15');
        }
        else if( ( (integer) date('i', $end) > 15 ) && 
            ( (integer) date('i', $end) <= 30 ) )
        {
            $end = strtotime(date('G', $end) . ':30');
        }
        else if( ( (integer) date('i', $end) > 30 ) && 
            ( (integer) date('i', $end) <= 45 ) )
        {
            $end = strtotime(date('G', $end) . ':45');
        }
        else
        {
            $h = date('G', $end);

            $h++;

            $end = strtotime($h . ':00');
        }

        $time_is_over = ( $time_is_over && ( time() > $end ) );

        while( $start <= $end )
        {
            if( date('d') === date('d', $start) )
            {
                $time[date('H:i', $start)] = date('g:i a', $start);
            }
            else
            {
                $time['24:00'] = '12:00 pm';
            }

            $start = $start + 900;
        }

        $start = strtotime($row->second_half_from);
        $end = strtotime($row->second_half_to);

        if( $row->timing_for === 'D' )
        {
            $delivery_time = $this->db->where('type', 'delivery_time')->get('sitesetting')->row()->value;

            $start += (integer) $delivery_time * 60;
        }

        if( (integer) date('i', $start) === 0 )
        {
            $start = strtotime(date('G', $start) . ':00');
        }
        else if( ( (integer) date('i', $start) > 0 ) && 
            ( (integer) date('i', $start) <= 15 ) )
        {
            $start = strtotime(date('G', $start) . ':15');
        }
        else if( ( (integer) date('i', $start) > 15 ) && 
            ( (integer) date('i', $start) <= 30 ) )
        {
            $start = strtotime(date('G', $start) . ':30');
        }
        else if( ( (integer) date('i', $start) > 30 ) && 
            ( (integer) date('i', $start) <= 45 ) )
        {
            $start = strtotime(date('G', $start) . ':45');
        }
        else
        {
            $h = date('G', $start);

            $h++;

            $start = strtotime($h . ':00');
        }

        if( (integer) date('i', $end) === 0 )
        {
            $end = strtotime(date('G', $end) . ':00');
        }
        else if( ( (integer) date('i', $end) > 0 ) && 
            ( (integer) date('i', $end) <= 15 ) )
        {
            $end = strtotime(date('G', $end) . ':15');
        }
        else if( ( (integer) date('i', $end) > 15 ) && 
            ( (integer) date('i', $end) <= 30 ) )
        {
            $end = strtotime(date('G', $end) . ':30');
        }
        else if( ( (integer) date('i', $end) > 30 ) && 
            ( (integer) date('i', $end) <= 45 ) )
        {
            $end = strtotime(date('G', $end) . ':45');
        }
        else
        {
            $h = date('G', $end);

            $h++;

            $end = strtotime($h . ':00');
        }

        $time_is_over = ( $time_is_over && ( time() > $end ) );

        while( $start <= $end )
        {
            if( date('d') === date('d', $start) )
            {
                $time[date('H:i', $start)] = date('g:i a', $start);
            }
            else
            {
                $time['24:00'] = '12:00 pm';
            }

            $start = $start + 900;
        }

        $time['time_is_over'] = $time_is_over;

        return $time;
    }
    
    /**
     * Helper for hours
     * @param $row
     * @return array|bool
     */
    private function formatTimesForSchedule($row) {
        $time = array();

        $start = strtotime($row->first_half_from);
        $end = strtotime($row->first_half_to);

        $time_is_over = true;

        if( $row->timing_for === 'P' )
        {
            if( (integer) date('i', $start) === 0 )
            {
                $start_time = date('G', $start) . ':00';
            }
            else if( ( (integer) date('i', $start) > 0 ) && 
                ( (integer) date('i', $start) <= 15 ) )
            {
                $start_time = date('G', $start) . ':15';
            }
            else if( ( (integer) date('i', $start) > 15 ) && 
                ( (integer) date('i', $start) <= 30 ) )
            {
                $start_time = date('G', $start) . ':30';
            }
            else if( ( (integer) date('i', $start) > 30 ) && 
                ( (integer) date('i', $start) <= 45 ) )
            {
                $start_time = date('G', $start) . ':45';
            }
            else
            {
                $h = date('G', $start);

                $h++;

                $start_time = $h . ':00';
            }

            $time['start_time'] = $start_time;
        }

        if( time() > $start )
        {
            $start = time();
        }

        if( $row->timing_for === 'D' )
        {
            $delivery_time = $this->db->where('type', 'delivery_time')->get('sitesetting')->row()->value;

            $start += (integer) $delivery_time * 60;
        }

        if( (integer) date('i', $start) === 0 )
        {
            $start = strtotime(date('G', $start) . ':00');
        }
        else if( ( (integer) date('i', $start) > 0 ) && 
            ( (integer) date('i', $start) <= 15 ) )
        {
            $start = strtotime(date('G', $start) . ':15');
        }
        else if( ( (integer) date('i', $start) > 15 ) && 
            ( (integer) date('i', $start) <= 30 ) )
        {
            $start = strtotime(date('G', $start) . ':30');
        }
        else if( ( (integer) date('i', $start) > 30 ) && 
            ( (integer) date('i', $start) <= 45 ) )
        {
            $start = strtotime(date('G', $start) . ':45');
        }
        else
        {
            $h = date('G', $start);

            $h++;

            $start = strtotime($h . ':00');
        }

        if( (integer) date('i', $end) === 0 )
        {
            $end = strtotime(date('G', $end) . ':00');
        }
        else if( ( (integer) date('i', $end) > 0 ) && 
            ( (integer) date('i', $end) <= 15 ) )
        {
            $end = strtotime(date('G', $end) . ':15');
        }
        else if( ( (integer) date('i', $end) > 15 ) && 
            ( (integer) date('i', $end) <= 30 ) )
        {
            $end = strtotime(date('G', $end) . ':30');
        }
        else if( ( (integer) date('i', $end) > 30 ) && 
            ( (integer) date('i', $end) <= 45 ) )
        {
            $end = strtotime(date('G', $end) . ':45');
        }
        else
        {
            $h = date('G', $end);

            $h++;

            $end = strtotime($h . ':00');
        }

        $time_is_over = ( $time_is_over && ( time() > $end ) );

        while( $start <= $end )
        {
            if( date('d') === date('d', $start) )
            {
                $time[date('H:i', $start)] = date('g:i a', $start);
            }
            else
            {
                $time['24:00'] = '12:00 pm';
            }

            $start = $start + 900;
        }

        $start = strtotime($row->second_half_from);
        $end = strtotime($row->second_half_to);

        if( time() > $start )
        {
            $start = time();
        }

        if( $row->timing_for === 'D' )
        {
            $delivery_time = $this->db->where('type', 'delivery_time')->get('sitesetting')->row()->value;

            $start += (integer) $delivery_time * 60;
        }

        if( (integer) date('i', $start) === 0 )
        {
            $start = strtotime(date('G', $start) . ':00');
        }
        else if( ( (integer) date('i', $start) > 0 ) && 
            ( (integer) date('i', $start) <= 15 ) )
        {
            $start = strtotime(date('G', $start) . ':15');
        }
        else if( ( (integer) date('i', $start) > 15 ) && 
            ( (integer) date('i', $start) <= 30 ) )
        {
            $start = strtotime(date('G', $start) . ':30');
        }
        else if( ( (integer) date('i', $start) > 30 ) && 
            ( (integer) date('i', $start) <= 45 ) )
        {
            $start = strtotime(date('G', $start) . ':45');
        }
        else
        {
            $h = date('G', $start);

            $h++;

            $start = strtotime($h . ':00');
        }

        if( (integer) date('i', $end) === 0 )
        {
            $end = strtotime(date('G', $end) . ':00');
        }
        else if( ( (integer) date('i', $end) > 0 ) && 
            ( (integer) date('i', $end) <= 15 ) )
        {
            $end = strtotime(date('G', $end) . ':15');
        }
        else if( ( (integer) date('i', $end) > 15 ) && 
            ( (integer) date('i', $end) <= 30 ) )
        {
            $end = strtotime(date('G', $end) . ':30');
        }
        else if( ( (integer) date('i', $end) > 30 ) && 
            ( (integer) date('i', $end) <= 45 ) )
        {
            $end = strtotime(date('G', $end) . ':45');
        }
        else
        {
            $h = date('G', $end);

            $h++;

            $end = strtotime($h . ':00');
        }

        $time_is_over = ( $time_is_over && ( time() > $end ) );

        while( $start <= $end )
        {
            if( date('d') === date('d', $start) )
            {
                $time[date('H:i', $start)] = date('g:i a', $start);
            }
            else
            {
                $time['24:00'] = '12:00 pm';
            }

            $start = $start + 900;
        }

        $time['time_is_over'] = $time_is_over;

        return $time;
    }

    /**
     * Get static page by type
     * @param $name
     * @return mixed
     */
    public function getPageByType($name) {
        switch( $name )
        {
            case 'about-us':
                $id = $this::PAGE_ABOUT;

                break;

            case 'order-success':
                $id = $this::PAGE_ORDER_SUCCESS;

                break;

            case 'order-failed':
                $id = $this::PAGE_ORDER_FAILED;

                break;

            default:
                $id = $this::PAGE_404;

                break;
        }

        $page = $this->db->get_where('tbl_pages', array('pageid' => $id))->row();

        return $page;
    }

    /**
     * Get Suburbs
     * @return mixed
     */
    public function getSub(){

        $suburb = $this->db->get('tbl_suburb')->result();

        return $suburb;

    }

    /**
     * Get States
     * @return mixed
     */
    public function getStates(){
        $states = $this->db->get('mast_state')->result();

        return $states;
    }

    /**
     * Verify if coupon is active
     * @param $coupon : coupon code
     * @return mixed
     */
    public function getCoupons($coupon){

        $now = date('Y-m-d');
        $getCoupon = $this->db->where('couponcode', $coupon)->
            where('status', 'active')->
            where('expirydate >=',$now)->
            get('tbl_coupon')->
            row();

        if($getCoupon){
            return $getCoupon;
        } else {
            return false;
        }

    }

    /**
     * Get Coupon
     * @param $couponId
     * @return bool
     */
    public function getCoupon($couponId){
        $coupon = $this->db->select('couponcode, discountper')->where('id', $couponId)->get('tbl_coupon')->row_array();

        if($coupon){
            return $coupon;
        } else {
            return false;
        }
    }

    /**
     * Get Register Text
     * @return mixed
     */
    public function getRegisterText(){
        $text = $this->db->select('value')->where('type', 'pop_h1')->get('tbl_manage_text')->row();
        return $text->value;
    }


    /**
     * Get session from the outher site
     * @param $orderHash
     * @param $ip
     * @param $browser
     * @return mixed
     */
    public function getSession($orderHash, $ip, $browser){
        $time = time() + 61;
        $session = $this->db->select('user_data')
            ->where('session_id', $orderHash)
            ->where('ip_address', $ip)
            ->where('user_agent', $browser)
            ->where('last_activity <', $time)
            ->get('mobile_sessions')
            ->row();
        if($session){
            return $session->user_data;
        } else {
            return false;
        }

    }

}