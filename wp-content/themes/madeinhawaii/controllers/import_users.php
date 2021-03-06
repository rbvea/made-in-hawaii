<?php
  use League\Csv\Reader;
  use Qaribou\Collection\ImmArray;

  $reader = Reader::createFromPath('_doc/vendors.csv');
  $results = ImmArray::fromArray($reader->fetchAll());
  /*
   prodid
   prodname
   active
   omit
   needsupdate
   needsupdatese
   updated
   MP
   address
   city
   state
   zip
   island
   phone1
   phone2
   fax_pre
   fax
   website
   email
   certified
   country
   estyr
   products
   products_info
   notes1
   notes2
   employ
   annual_volume
   exporter
   export_sales
   title1
   salutation1
   salutation2
   */
  global $wpdb;
  $str =
    $results
      ->map(function($row) use($wpdb) {
        $fields = ImmArray::fromArray(acf_field_group::get_fields(null, 30));
        $mapping = [
          'active' => 3,
          'omit' => 4,
          'address' => 7,
          'city' => 8,
          'state' => 9,
          'zip' => 10,
          'island' => 11,
          'phone1' => 12,
          'phone2' => 13,
          'fax_pre' => 14,
          'fax' => 15,
          'website' => 16,
          'certified' => 18,
          'country' => 19,
          'established_year' => 20,
        ];

        $id = intval($row[0]);
        if(!empty($row[17])) {
          return;
        }

        $nicename = sanitize_key($row[1]);

        $args = [
          'ID' => $id,
          'user_nicename' => substr($nicename, 0, 50),
          'user_login' => substr($nicename, 0, 60),
          'user_url' => $row[16],
          'user_email' => 'madewithaloha@hawaii.gov',
          'display_name' => $row[1],
          'user_status' => 0,
          'user_activation_key' => '',
          'user_registered' => date("Y-m-d H:i:s"),
          'user_pass' => ''
        ];
        $arg_format = [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s'
          ];
        $user = new WP_User($id);
        if(!$user->ID) {
          $res = $wpdb->insert('wp_users', $args, $arg_format);
        } else {
          // shift off id
          array_shift($args);
          array_shift($arg_format);

          $res = $wpdb->update('wp_users', $args, ['ID' => $id], $arg_format);
        }
        if(!$res) {
          die(var_dump($args));
        }
        foreach($mapping as $key => $index) {
          $field = $fields->filter(function($field) use($key) {
            $field['key'] == $key;
          })[0];
          update_user_meta($id, $key, $row[$index]);
          update_user_meta($id, '_' . $key, $field['key']);
        }
        return $row[1];
      });
    echo $str->join('<br>');

?>
