<?php
/*
Copyright (C) 1998, 1999, 2000, 2001 Afan Ottenheimer.  Released under
the GPL and PHP licenses as stated in the the README file which
should have been included with this document.

World Coordinates are the XY coordinates relative to the
axis origin that can be drawn. Not the device (pixel) coordinates
which in GD is relative to the origin at the upper left
side of the image.
*/

//PHPLOT Version 4.4.6
//Requires PHP 3.0.2 or later 


class PHPlot
{

  var $is_inline = 0;			//0 = Sends headers, 1 = sends just raw image data
  var $browser_cache = '1';	// 0 = Sends headers for browser to not cache the image, (i.e. 0 = don't let browser cache image)
									// (only if is_inline = 0 also)
  var $session_set = '';		//Do not change
  var $scale_is_set = '';		//Do not change
  var $draw_plot_area_background = '';
  
  var $image_width;	//Total Width in Pixels 
  var $image_height; 	//Total Height in Pixels
  var $image_border_type = ''; //raised, plain, ''
  var $x_left_margin;
  var $y_top_margin;
  var $x_right_margin;
  var $y_bot_margin;
  var $plot_area = array(5,5,600,400);
  var $x_axis_position = 0;	//Where to draw the X_axis (world coordinates)
  var $y_axis_position = '';  //Leave blank for Y axis at left of plot. (world coord.)
  var $xscale_type = 'linear';  //linear or log
  var $yscale_type = 'linear';
  
  //Use for multiple plots per image
  var $print_image = 1;  //Used for multiple charts per image. 
  
  //Fonts
  var $use_ttf  = 0;		  //Use TTF fonts (1) or not (0)
  var $font_path = './';  //To be added 
  var $font = './benjamingothic.ttf';
  
  ///////////Fonts: Small/Generic
  var $small_ttffont_size = 12; //
  //non-ttf
  var $small_font = 2; // fonts = 1,2,3,4 or 5
  var $small_font_width = 6.0; // width in pixels (2=6,3=8,4=8)
  var $small_font_height = 8.0; // height in pixels (2=8,3=10,4=12)
  
  //////////   Fonts:Title
  var $title_ttffont = './benjamingothic.ttf';
  var $title_ttffont_size = 14;
  var $title_angle= 0;
  //non-ttf
  var $title_font = '4'; // fonts = 1,2,3,4,5
  
  //////////////  Fonts:Axis
  var $axis_ttffont = './benjamingothic.ttf';
  var $axis_ttffont_size = 8;
  var $x_datalabel_angle = 0;
  //non-ttf
  var $axis_font = 2;
  
  ////////////////Fonts:Labels of Data
  var $datalabel_font = '2';
  
  //////////////// Fonts:Labels (Axis Titles)
  var $x_label_ttffont = './benjamingothic.ttf';
  var $x_label_ttffont_size = '12';
  var $x_label_angle = '0';
  
  var $y_label_ttffont = './benjamingothic.ttf';
  var $y_label_ttffont_size = '12';
  var $y_label_angle = 90;
  var $y_label_width = '';
  
  //Formats
  var $file_format = 'png';
  var $file_name = '';  //For output to a file instead of stdout
  
  //Plot Colors
  var $shading = 0;
  var $color_array = 1;	//1 = include small list
  //2 = include large list
  //array =  define your own color translation. See rgb.inc.php and SetRGBArray
  var $bg_color;
  var $plot_bg_color;
  var $grid_color;
  var $light_grid_color;
  var $tick_color;
  var $title_color;
  var $label_color;
  var $text_color;
  var $i_light = '';
  
  //Data
  var $data_type = 'text-data'; //text-data, data-data-error, data-data 
  var $plot_type= 'linepoints'; //bars, lines, linepoints, area, points, pie, thinbarline
  var $line_width = 2;
  var $line_style = array('solid','solid','solid','dashed','dashed','solid'); //Solid or dashed lines
  
  var $data_color = ''; //array('blue','green','yellow',array(0,0,0));
  var $data_border_color = '';
  
  var $label_scale_position = '.5';  //1 = top, 0 = bottom
  var $group_frac_width = '.7'; //value from 0 to 1 = width of bar
  var $bar_width_adjust = '1'; //1 = bars of normal width, must be > 0
  
  var $point_size = 10;
  var $point_shape = 'diamond'; //rect,circle,diamond,triangle,dot,line,halfline
  var $error_bar_shape = 'tee'; //tee, line
  var $error_bar_size = 5; //right left size of tee
  var $error_bar_line_width = ''; //If set then use it, else use $line_width for thickness
  var $error_bar_color = ''; 
  var $data_values;
  
  var $plot_border_type = 'full'; //left, none, full
  var $plot_area_width = '';
  var $number_x_points;
  var $plot_min_x; // Max and min of the plot area
  var $plot_max_x= ''; // Max and min of the plot area
  var $plot_min_y= ''; // Max and min of the plot area
  var $plot_max_y = ''; // Max and min of the plot area
  var $min_y = '';
  var $max_y = '';
  var $max_x = 10;  //Must not be = 0;
  var $y_precision = '1';
  var $x_precision = '1';
  var $si_units = '';
  
  //Labels
  var $draw_data_labels = '0';  
  var $legend = '';  //an array
  var $legend_x_pos = '';
  var $legend_y_pos = '';
  var $title_txt = "";
  var $y_label_txt = '';
  var $x_label_txt = "";
  
  //DataAxis Labels (on each axis)
  var $y_grid_label_type = 'data';    //data, none, time, other
  var $y_grid_label_pos = 'plotleft'; //plotleft, plotright, yaxis, both
  var $x_grid_label_type = 'data';    //data, title, none, time, other
  var $draw_x_data_labels = '';       // 0=false, 1=true, ""=let program decide 
  var $x_time_format = "%H:%m:%s";    //See http://www.php.net/manual/html/function.strftime.html
  var $x_datalabel_maxlength = 10;	
  
  //Tick Formatting
  var $tick_length = '10';   //pixels: tick length from axis left/downward
  //tick_length2 to be implemented
  //var $tick_length2 = '';  //pixels: tick length from axis line rightward/upward
  var $draw_vert_ticks = 1;  //1 = draw ticks, 0 = don't draw ticks
  var $num_vert_ticks = '';
  var $vert_tick_increment=''; //Set num_vert_ticks or vert_tick_increment, not both.
  var $vert_tick_position = 'both'; //plotright=(right of plot only), plotleft=(left of plot only), 
  //both = (both left and right of plot), yaxis=(crosses y axis)
  var $horiz_tick_increment=''; //Set num_horiz_ticks or horiz_tick_increment, not both.
  var $num_horiz_ticks='';
  var $skip_top_tick = '0';
  var $skip_bottom_tick = '0';
  
  //Grid Formatting
  var $draw_x_grid = 0;
  var $draw_y_grid = 1;
  
  
  //BEGIN CODE
  //////////////////////////////////////////////////////
  //Constructor: Setup Img pointer, Colors and Size of Image
  function PHPlot($which_width=600,$which_height=400,$which_output_file="",$which_input_file="") {
    
    $this->SetRGBArray('2'); 
    $this->background_done = 0; //Set to 1 after background image first drawn
    
    if ($which_output_file != "") { $this->SetOutputFile($which_output_file);  };
    
    if ($which_input_file != "") { 
      $this->SetInputFile($which_input_file) ; 
    } else { 
      $this->SetImageArea($which_width, $which_height);
      $this->InitImage();
    }
    
    if ( ($this->session_set == 1) && ($this->img == "") ) {  //For sessions
      //Do nothing
    } else { 
      $this->SetDefaultColors();
    }
    
    $this->SetIndexColors();
    
  }
  
  //Set up the image and colors
  function InitImage() {
    //if ($this->img) { 
    //	ImageDestroy($this->img);
    //}
    $this->img = ImageCreate($this->image_width, $this->image_height);
    return true;
  }
  
  function SetBrowserCache($which_browser_cache) {  //Submitted by Thiemo Nagel
    $this->browser_cache = $which_browser_cache;
    return true;
  }
  
  function SetPrintImage($which_pi) {
    $this->print_image = $which_pi;
    return true;
  }
  
  function SetIsInline($which_ii) {
    $this->is_inline = $which_ii;
    return true;
  }
  
  function SetUseTTF($which_ttf) {
    $this->use_ttf = $which_ttf;
    return true;
  }
  
  function SetTitleFontSize($which_tfs) {
    //TTF
    $this->title_ttffont_size = $which_tfs; //pt size
    
    //Non-TTF settings
    if (($which_tfs > 5) && (!$this->use_ttf)) {
      $this->DrawError('Non-TTF font size must be 1,2,3,4 or 5');
      return false;
    } else {
      $this->title_font = $which_tfs;
      //$this->title_font_height = ImageFontHeight($which_tfs) // height in pixels 
      //$this->title_font_width = ImageFontWidth($which_tfs); // width in pixels 
    }
    return true;
  }
  
  function SetLineStyles($which_sls){
    $this->line_style = $which_sls;
    return true;
  }

  function SetLegend($which_leg){
    if (is_array($which_leg)) { 
      $this->legend = $which_leg;
      return true;
    } else { 
      $this->DrawError('Error: SetLegend argument must be an array');
      return false;
    }
  }

  function SetLegendPixels($which_x,$which_y,$which_type) { 
    //which_type not yet used
    $this->legend_x_pos = $which_x;
    $this->legend_y_pos = $which_y;
    return true;
  }

  function SetLegendWorld($which_x,$which_y,$which_type='') { 
    //which_type not yet used
    //Must be called after scales are set up. 
    if ($this->scale_is_set != 1) { $this->SetTranslation(); };
    $this->legend_x_pos = $this->xtr($which_x);
    $this->legend_y_pos = $this->ytr($which_y);
    return true;
  }
/* ***************************************
   function SetFileFormat($which_file_format) { //Only works with PHP4
   $asked = strtolower($which_file_format);
   if( $asked =="jpg" || $asked =="png" || $asked =="gif" || $asked =="wbmp" ) {
   if( $asked=="jpg" && !(imagetypes() & IMG_JPG) )
   return false;
   elseif( $asked=="png" && !(imagetypes() & IMG_PNG) ) 
   return false;
   elseif( $asked=="gif" && !(imagetypes() & IMG_GIF) ) 	
   return false;
   elseif( $asked=="wbmp" && !(imagetypes() & IMG_WBMP) ) 	
   return false;
   else {
   $this->img_format=$asked;
   return true;
   }
   }
   else
   return false;
   }	

   *************************************** */
  function SetFileFormat($which_file_format) {
    //eventually test to see if that is supported - if not then return false
    $asked = strtolower(trim($which_file_format));
    if( ($asked=='jpg') || ($asked=='png') || ($asked=='gif') || ($asked=='wbmp') ) {
      $this->file_format = $asked;
      return true;
    } else {
      return false;
    }
  }
  
  function SetInputFile($which_input_file) { 
    //$this->SetFileFormat($which_frmt);
    $size = GetImageSize($which_input_file);
    $input_type = $size[2]; 
    
    switch($input_type) {  //After SetFileFormat is in lower case
    case "1":
      $im = @ImageCreateFromGIF ($which_input_file);
      if (!$im) { // See if it failed 
	$this->PrintError("Unable to open $which_input_file as a GIF");
	return false;
      }
      break;
    case "3":
      $im = @ImageCreateFromPNG ($which_input_file); 
      if (!$im) { // See if it failed 
	$this->PrintError("Unable to open $which_input_file as a PNG");
	return false;
      }
      break;
    case "2":
      $im = @ImageCreateFromJPEG ($which_input_file); 
      if (!$im) { // See if it failed 
	$this->PrintError("Unable to open $which_input_file as a JPG");
	return false;
      }
      break;
    default:
      $this->PrintError('Please select wbmp,gif,jpg, or png for image type!');
      return false;
      break;
    }
    
    //Get Width and Height of Image
    $this->SetImageArea($size[0],$size[1]);
    
    $this->img = $im;
    
    return true;
    
  }

  function SetOutputFile($which_output_file)
  { 
    $this->output_file = $which_output_file;
    return true;
  }
  
  function SetImageArea($which_iw,$which_ih) {
    //Note this is now an Internal function - please set w/h via PHPlot()
    $this->image_width = $which_iw;
    $this->image_height = $which_ih;
    
    return true;
  }
  
  function SetYAxisPosition($which_pos) 
  {
    $this->y_axis_position = $which_pos;
    return true;
  }
  
  function SetXAxisPosition($which_pos) 
  {
    $this->x_axis_position = $which_pos;
    return true;
  }
  
  function SetXTimeFormat($which_xtf)
  {
    $this->x_time_format = $which_xtf;
    return true;
  }
	
  function SetXDataLabelMaxlength($which_xdlm) { 
    if ($which_xdlm >0 ) { 
      $this->x_datalabel_maxlength = $which_xdlm;
      return true;
    } else { 
      return false;
    }
  }

  function SetXDataLabelAngle($which_xdla) { 
    $this->x_datalabel_angle = $which_xdla;
    return true;
  }

  function SetXScaleType($which_xst) { 
    $this->xscale_type = $which_xst;
    return true;
  }
	
  function SetYScaleType($which_yst) { 
    $this->yscale_type = $which_yst;
    if ($this->x_axis_position <= 0) { 
      $this->x_axis_position = 1;
    }
    return true;
  }
  
  function SetPrecisionX($which_prec) {
    $this->x_precision = $which_prec;
    return true;
  }

  function SetPrecisionY($which_prec)
  {
    $this->y_precision = $which_prec;
    return true;
  }


	function SetIndexColors() { //Internal Method called to set colors and preserve state
		//These are the colors of the image that are used. They are initialized
		//to work with sessions and PHP. 

		$this->ndx_i_light = $this->SetIndexColor($this->i_light);
		$this->ndx_i_dark  = $this->SetIndexColor($this->i_dark);
		$this->ndx_bg_color= $this->SetIndexColor($this->bg_color);
		$this->ndx_plot_bg_color= $this->SetIndexColor($this->plot_bg_color);

		$this->ndx_title_color= $this->SetIndexColor($this->title_color);
		$this->ndx_tick_color= $this->SetIndexColor($this->tick_color);
		$this->ndx_label_color= $this->SetIndexColor($this->label_color);
		$this->ndx_text_color= $this->SetIndexColor($this->text_color);
		$this->ndx_light_grid_color= $this->SetIndexColor($this->light_grid_color);
		$this->ndx_grid_color= $this->SetIndexColor($this->grid_color);

		reset($this->error_bar_color);  
		unset($ndx_error_bar_color);
		$i = 0; 
		while (list(, $col) = each($this->error_bar_color)) {
		  $this->ndx_error_bar_color[$i] = $this->SetIndexColor($col);
			$i++;
		}
		//reset($this->data_border_color);
		unset($ndx_data_border_color);
		$i = 0;
		while (list(, $col) = each($this->data_border_color)) {
			$this->ndx_data_border_color[$i] = $this->SetIndexColor($col);
			$i++;
		}
		//reset($this->data_color); 
		unset($ndx_data_color);
		$i = 0;
		while (list(, $col) = each($this->data_color)) {
			$this->ndx_data_color[$i] = $this->SetIndexColor($col);
			$i++;
		}

		return true;
	}


  function SetDefaultColors()
  {
    $this->i_light = array(194,194,194);
    $this->i_dark =  array(100,100,100);
    $this->SetPlotBgColor(array(222,222,222));
    $this->SetBackgroundColor(array(200,222,222)); //can use rgb values or "name" values
    $this->SetLabelColor('black');
    $this->SetTextColor('black');
    $this->SetGridColor('black');
    $this->SetLightGridColor(array(175,175,175));
    $this->SetTickColor('black');
    $this->SetTitleColor(array(0,0,0)); // Can be array or name
    $this->data_color = array('green','blue','yellow','red','orange');
    $this->error_bar_color = array('blue','green','yellow','red','orange');
    $this->data_border_color = array('black');

    $this->session_set = 1; //Mark it down for PHP session() usage.
  }

  function PrintImage() 
  {

    if ( ($this->browser_cache == 0) && ($this->is_inline == 0)) { //Submitted by Thiemo Nagel
      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
      header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . 'GMT');
      header('Cache-Control: no-cache, must-revalidate');
      header('Pragma: no-cache');
    }

    switch($this->file_format) {
    case "png":
      if ($this->is_inline == 0) {
	Header('Content-type: image/png');
      }
      if ($this->is_inline == 1 && $this->output_file != "") {
	ImagePng($this->img,$this->output_file);
      } else {
	ImagePng($this->img);
      }
      break;
    case "jpg":
      if ($this->is_inline == 0) {
	Header('Content-type: image/jpeg');
      }
      if ($this->is_inline == 1 && $this->output_file != "") {
	ImageJPEG($this->img,$this->output_file);
      } else {
	ImageJPEG($this->img);
      }
      break;
    case "gif":
      if ($this->is_inline == 0) {
	Header('Content-type: image/gif');
      }
      if ($this->is_inline == 1 && $this->output_file != "") {
	ImageGIF($this->img,$this->output_file);
      } else {
	ImageGIF($this->img);
      }
      
      break;
    case "wbmp":
      if ($this->is_inline == 0) {
	Header('Content-type: image/wbmp');
      }
      if ($this->is_inline == 1 && $this->output_file != "") {
	ImageWBMP($this->img,$this->output_file);
      } else {
	ImageWBMP($this->img);
      }
      
      break;
    default:
      $this->PrintError('Please select an image type!<br>');
      break;
    }
    ImageDestroy($this->img);
    return true;
  }


  function DrawBackground() {
    //if ($this->img == "") { $this->InitImage(); };
    if ($this->background_done == 0) { //Don't draw it twice if drawing two plots on one image
      ImageFilledRectangle($this->img, 0, 0,
			   $this->image_width, $this->image_height, $this->ndx_bg_color);
      $this->background_done = 1;
    }
    return true;
  }
  
  function DrawImageBorder() {
    switch ($this->image_border_type) {
    case "raised":
      ImageLine($this->img,0,0,$this->image_width-1,0,$this->ndx_i_light);
      ImageLine($this->img,1,1,$this->image_width-2,1,$this->ndx_i_light);
      ImageLine($this->img,0,0,0,$this->image_height-1,$this->ndx_i_light);
      ImageLine($this->img,1,1,1,$this->image_height-2,$this->ndx_i_light);
      ImageLine($this->img,$this->image_width-1,0,$this->image_width-1,$this->image_height-1,$this->ndx_i_dark);
      ImageLine($this->img,0,$this->image_height-1,$this->image_width-1,$this->image_height-1,$this->ndx_i_dark);
      ImageLine($this->img,$this->image_width-2,1,$this->image_width-2,$this->image_height-2,$this->ndx_i_dark);
      ImageLine($this->img,1,$this->image_height-2,$this->image_width-2,$this->image_height-2,$this->ndx_i_dark);
      break;
    case "plain":
				ImageLine($this->img,0,0,$this->image_width,0,$this->ndx_i_dark);
				ImageLine($this->img,$this->image_width-1,0,$this->image_width-1,$this->image_height,$this->ndx_i_dark);
				ImageLine($this->img,$this->image_width-1,$this->image_height-1,0,$this->image_height-1,$this->ndx_i_dark);
				ImageLine($this->img,0,0,0,$this->image_height,$this->ndx_i_dark);
			break;
			default:
			break;
		}
		return true;
	}

	function SetPlotBorderType($which_pbt) {
		$this->plot_border_type = $which_pbt; //left, none, anything else=full
	}

	function SetImageBorderType($which_sibt) {
		$this->image_border_type = $which_sibt; //raised, plain
	}

	function SetDrawPlotAreaBackground($which_dpab) {
		$this->draw_plot_area_background = $which_dpab;  // 1=true or anything else=false
	}

	function SetDrawDataLabels($which_ddl) {  //Draw next to datapoints
		$this->draw_data_labels = $which_ddl;  // 1=true or anything else=false
	}

	function SetDrawXDataLabels($which_dxdl) {  //Draw on X Axis
		$this->draw_x_data_labels = $which_dxdl;  // 1=true or anything else=false
	}

	function SetDrawYGrid($which_dyg) {
		$this->draw_y_grid = $which_dyg;  // 1=true or anything else=false
	}

	function SetDrawXGrid($which_dxg) {
		$this->draw_x_grid = $which_dxg;  // 1=true or anything else=false
	}

	function SetYGridLabelType($which_yglt) {
		$this->y_grid_label_type = $which_yglt;
		return true;
	}

	function SetXGridLabelType($which_xglt) {
		$this->x_grid_label_type = $which_xglt;
		return true;
	}

	function SetXLabel($xlbl) {
		$this->x_label_txt = $xlbl;
		return true;
	}
	function SetYLabel($ylbl) {
		$this->y_label_txt = $ylbl;
		return true;
	}
	function SetTitle($title) {
		$this->title_txt = $title;
		return true;
	}

	//function SetLabels($xlbl,$ylbl,$title) {
	//	$this->title_txt = $title;
	//	$this->x_label_txt = $xlbl;
	//	$this->y_label_txt = $ylbl;
	//}

	function DrawLabels() {
		$this->DrawTitle();
		$this->DrawXLabel();
		$this->DrawYLabel();
		return true;
	}

	function DrawXLabel() {
		if ($this->use_ttf == 1) { 
			$xpos = $this->xtr(($this->plot_max_x + $this->plot_min_x)/2.0) ;
			$ypos = $this->ytr($this->plot_min_y) + $this->x_label_height/2.0;
			$this->DrawText($this->x_label_ttffont, $this->x_label_angle,
				$xpos, $ypos, $this->ndx_label_color, $this->x_label_ttffont_size, $this->x_label_txt,'center');
		} else { 
			//$xpos = 0.0 - (ImageFontWidth($this->small_font)*strlen($this->x_label_txt)/2.0) + $this->xtr(($this->plot_max_x+$this->plot_min_x)/2.0) ;
			$xpos = 0.0 + $this->xtr(($this->plot_max_x+$this->plot_min_x)/2.0) ;
			$ypos = ($this->ytr($this->plot_min_y) + $this->x_label_height/2);

			$this->DrawText($this->small_font, $this->x_label_angle, 
				$xpos, $ypos, $this->ndx_label_color, "", $this->x_label_txt, 'center');

		}
		return true;
	}

	function DrawYLabel() {
		if ($this->use_ttf == 1) { 
			$size = $this->TTFBBoxSize($this->y_label_ttffont_size, 90, $this->y_label_ttffont, $this->y_label_txt);
			$xpos = 8 + $size[0];
			$ypos = ($size[1])/2 + $this->ytr(($this->plot_max_y + $this->plot_min_y)/2.0) ;
			$this->DrawText($this->y_label_ttffont, 90,
				$xpos, $ypos, $this->ndx_label_color, $this->y_label_ttffont_size, $this->y_label_txt);
		} else { 
			$xpos = 8;
			$ypos = (($this->small_font_width*strlen($this->y_label_txt)/2.0) +
					$this->ytr(($this->plot_max_y + $this->plot_min_y)/2.0) );
			$this->DrawText($this->small_font, 90,
				$xpos, $ypos, $this->ndx_label_color, $this->y_label_ttffont_size, $this->y_label_txt);
		}
		return true;
	}

	function DrawText($which_font,$which_angle,$which_xpos,$which_ypos,$which_color,$which_size,$which_text,$which_halign='left',$which_valign='') {

		if ($this->use_ttf == 1 ) { 
			$size = $this->TTFBBoxSize($which_size, $which_angle, $which_font, $which_text); 
			if ($which_valign == 'bottom') { 
				$which_ypos = $which_ypos + ImageFontHeight($which_font);
			}
			if ($which_halign == 'center') { 
				$which_xpos = $which_xpos - $size[0]/2;
			}
			ImageTTFText($this->img, $which_size, $which_angle, 
				$which_xpos, $which_ypos, $which_color, $which_font, $which_text); 
		} else { 
			if ($which_valign == 'top') { 
				$which_ypos = $which_ypos - ImageFontHeight($which_font);
			}
			$which_text = ereg_replace("\r","",$which_text);
			$str = split("\n",$which_text); //multiple lines submitted by Remi Ricard
			$height = ImageFontHeight($which_font);
			$width = ImageFontWidth($which_font);
			if ($which_angle == 90) {  //Vertical Code Submitted by Marlin Viss
				for($i=0;$i<count($str);$i++) { 
					ImageStringUp($this->img, $which_font, ($i*$height + $which_xpos), $which_ypos, $str[$i], $which_color);
				} 
			} else {
				for($i=0;$i<count($str);$i++) { 
					if ($which_halign == 'center') { 
                    	$xpos = $which_xpos - strlen($str[$i]) * $width/2;
 						ImageString($this->img, $which_font, $xpos, ($i*$height + $which_ypos), $str[$i], $which_color);
					} else { 
						ImageString($this->img, $which_font, $which_xpos, ($i*$height + $which_ypos), $str[$i], $which_color); 
					}
				} 
			}

		} 
		return true; 

	}
	function DrawTitle() {
		if ($this->use_ttf == 1 ) { 
			$xpos = ($this->plot_area[0] + $this->plot_area_width / 2);
			$ypos = $this->y_top_margin/2;
			$this->DrawText($this->title_ttffont, $this->title_angle, 
				$xpos, $ypos, $this->ndx_title_color, $this->title_ttffont_size, $this->title_txt,'center'); 
		} else { 
			$xpos = ($this->plot_area[0] + $this->plot_area_width / 2);
			$ypos = ImageFontHeight($this->title_font); 
			$this->DrawText($this->title_font, $this->title_angle, 
				$xpos, $ypos, $this->ndx_title_color, '', $this->title_txt,'center'); 
		} 
		return true; 

	}

	function DrawPlotAreaBackground() {
		ImageFilledRectangle($this->img,$this->plot_area[0],
			$this->plot_area[1],$this->plot_area[2],$this->plot_area[3],
			$this->ndx_plot_bg_color);
	}

	function SetBackgroundColor($which_color) {
		$this->bg_color= $which_color;
		$this->ndx_bg_color= $this->SetIndexColor($which_color);
		return true;
	}
	function SetPlotBgColor($which_color) {
		$this->plot_bg_color= $which_color;
		$this->ndx_plot_bg_color= $this->SetIndexColor($which_color);
		return true;
	}

	function SetShading($which_s) { 
		$this->shading = $which_s;
		return true;
	}

	function SetTitleColor($which_color) {
		$this->title_color= $which_color;
		$this->ndx_title_color= $this->SetIndexColor($which_color);
		return true;
	}

	function SetTickColor ($which_color) {
		$this->tick_color= $which_color;
		$this->ndx_tick_color= $this->SetIndexColor($which_color);
		return true;
	}

	function SetLabelColor ($which_color) {
		$this->label_color= $which_color;
		$this->ndx_label_color= $this->SetIndexColor($which_color);
		return true;
	}

	function SetTextColor ($which_color) {
		$this->text_color= $which_color;
		$this->ndx_text_color= $this->SetIndexColor($which_color);
		return true;
	}

	function SetLightGridColor ($which_color) {
		$this->light_grid_color= $which_color;
		$this->ndx_light_grid_color= $this->SetIndexColor($which_color);
		return true;
	}

	function SetGridColor ($which_color) {
		$this->grid_color = $which_color;
		$this->ndx_grid_color= $this->SetIndexColor($which_color);
		return true;
	}

	function SetCharacterHeight() {
		//to be set
		return true;
	}

  function SetPlotType($which_pt)
  {
    $accepted = "bars,lines,linepoints,area,points,pie,thinbarline";
    $asked = trim($which_pt);
    if (eregi($asked, $accepted)) {
      $this->plot_type = $which_pt;
      return true;
    } else {
      $this->DrawError('$which_pt not an acceptable plot type');
      return false;
    }
  }

  function FindDataLimits() {
    //Text-Data is different than data-data graphs. For them what
    // we have, instead of X values, is # of records equally spaced on data.
    //text-data is passed in as $data[] = (title,y1,y2,y3,y4,...)
    //data-data is passed in as $data[] = (title,x,y1,y2,y3,y4,...) 
    
    $this->number_x_points = count($this->data_values);
    
    switch ($this->data_type) {
    case "text-data":
      $minx = 0; //valid for BAR TYPE GRAPHS ONLY
      $maxx = $this->number_x_points - 1 ;  //valid for BAR TYPE GRAPHS ONLY
      $miny = (double) $this->data_values[0][1];
      $maxy = $miny;
      if ($this->draw_x_data_labels == "") { 
	$this->draw_x_data_labels = 1;  //labels_note1: prevent both data labels and x-axis labels being both drawn and overlapping
      }
      break;
    default:  //Everything else: data-data, etc.
      $maxx = $this->data_values[0][1];
      $minx = $maxx;
      $miny = $this->data_values[0][2];
      $maxy = $miny;
      $maxy = $miny;
      break;
    }
    
    $max_records_per_group = 0;
    $total_records = 0;
    $mine = 0; //Maximum value for the -error bar (assume error bars always > 0) 
    $maxe = 0; //Maximum value for the +error bar (assume error bars always > 0) 
    
    reset($this->data_values);
    while (list($dat_key, $dat) = each($this->data_values)) {  //for each X barchart setting
      //foreach($this->data_values as $dat)  //can use foreach only in php4
      
      $tmp = 0;
      $total_records += count($dat) - 1; // -1 for label
      
      switch ($this->data_type) {
      case "text-data":
	//Find the relative Max and Min
	
	while (list($key, $val) = each($dat)) {
	  if ($key != 0) {  //$dat[0] = label
	    SetType($val,"double");
	    if ($val > $maxy) {
	      $maxy = $val ;
	    }
	    if ($val < $miny) {
	      $miny = (double) $val ;
	    }
	  }
	  $tmp++;
	}
	break;
      case "data-data":  //X-Y data is passed in as $data[] = (title,x,y,y2,y3,...) which you can use for multi-dimentional plots.

	while (list($key, $val) = each($dat)) {
	  if ($key == 1) {  //$dat[0] = label
	    SetType($val,"double");
	    if ($val > $maxx) {
	      $maxx = $val;
	    } elseif ($val < $minx) {
	      $minx = $val;
	    }
	  } elseif ($key > 1) {
	    SetType($val,"double");
	    if ($val > $maxy) {
	      $maxy = $val ;
	    } elseif ($val < $miny) {
	      $miny = $val ;
	    }
	  }
	  $tmp++;
	}
	$tmp = $tmp - 1; //# records per group
	break;
      case "data-data-error":  //Assume 2-D for now, can go higher
	//Regular X-Y data is passed in as $data[] = (title,x,y,error+,error-,y2,error2+,error2-)
	
	while (list($key, $val) = each($dat)) {
	  if ($key == 1) {  //$dat[0] = label
	    SetType($val,'double');
	    if ($val > $maxx) {
	      $maxx = $val;
	    } elseif ($val < $minx) {
	      $minx = $val;
	    }
	  } elseif ($key%3 == 2) {
	    SetType($val,'double');
	    if ($val > $maxy) {
	      $maxy = $val ;
	    } elseif ($val < $miny) {
	      $miny = $val ;
	    }
	  } elseif ($key%3 == 0) {
	    SetType($val,'double');
	    if ($val > $maxe) {
	      $maxe = $val ;
	    }
	  } elseif ($key%3 == 1) {
	    SetType($val,'double');
	    if ($val > $mine) {
	      $mine = $val ;
	    }
	  }
	  $tmp++;
	}
	$maxy = $maxy + $maxe;
	$miny = $miny - $mine; //assume error bars are always > 0
	
	break;
      default:
	$this->PrintError('ERROR: unknown chart type');
	break;
      }
      if ($tmp > $max_records_per_group) {
	$max_records_per_group = $tmp;
      }
    }
    
    $this->min_x = $minx;
    $this->max_x = $maxx;
    $this->min_y = $miny;
    $this->max_y = $maxy;
    
    if ($max_records_per_group > 1) {
      $this->records_per_group = $max_records_per_group - 1;
    } else {
      $this->records_per_group = 1;
    }
    

    //$this->data_count = $total_records ;
  } // function FindDataLimits
  
  function SetMargins() {
    /////////////////////////////////////////////////////////////////
    // When the image is first created - set the margins
    // to be the standard viewport.
    // The standard viewport is the full area of the view surface (or panel),
    // less a margin of 4 character heights all round for labelling.
    // It thus depends on the current character size, set by SetCharacterHeight().
    /////////////////////////////////////////////////////////////////

    $str = split("\n",$this->title_txt); 
    $nbLines = count($str); 

    if ($this->use_ttf == 1) {
      $title_size = $this->TTFBBoxSize($this->title_ttffont_size, $this->title_angle, $this->title_ttffont, 'X'); //An array
      if ($nbLines == 1) { 
	$this->y_top_margin = $title_size[1] * 4;
      } else { 
	$this->y_top_margin = $title_size[1] * ($nbLines+3);
      }

      //ajo working here
      //$x_label_size = $this->TTFBBoxSize($this->x_label_ttffont_size, 0, $this->axis_ttffont, $this->x_label_txt);

      $this->y_bot_margin = $this->x_label_height ;
      $this->x_left_margin = $this->y_label_width * 2 + $this->tick_length;
      $this->x_right_margin = 33.0; // distance between right and end of x axis in pixels 
    } else {
      $title_size = array(ImageFontWidth($this->title_font) * strlen($this->title_txt),ImageFontHeight($this->title_font));
      //$this->y_top_margin = ($title_size[1] * 4);
      if ($nbLines == 1) { 
	$this->y_top_margin = $title_size[1] * 4;
      } else { 
	$this->y_top_margin = $title_size[1] * ($nbLines+3);
      }
      if ($this->x_datalabel_angle == 90) {
	$this->y_bot_margin = 76.0; // Must be integer
      } else {
	$this->y_bot_margin = 66.0; // Must be integer
      }
      $this->x_left_margin = 77.0; // distance between left and start of x axis in pixels
      $this->x_right_margin = 33.0; // distance between right and end of x axis in pixels
    }

    //exit;
    $this->x_tot_margin = $this->x_left_margin + $this->x_right_margin;
    $this->y_tot_margin = $this->y_top_margin + $this->y_bot_margin;

    if ($this->plot_max_x && $this->plot_max_y && $this->plot_area_width ) { //If data has already been analysed then set translation
      $this->SetTranslation();
    }
  }

  function SetMarginsPixels($which_lm,$which_rm,$which_tm,$which_bm) { 
    //Set the plot area using margins in pixels (left, right, top, bottom)
    $this->SetNewPlotAreaPixels($which_lm,$which_tm,($this->image_width - $which_rm),($this->image_height - $which_bm));
    return true;
  }

  function SetNewPlotAreaPixels($x1,$y1,$x2,$y2) {
    //Like in GD 0,0 is upper left set via pixel Coordinates
    $this->plot_area = array($x1,$y1,$x2,$y2);
    $this->plot_area_width = $this->plot_area[2] - $this->plot_area[0];
    $this->plot_area_height = $this->plot_area[3] - $this->plot_area[1];
    $this->y_top_margin = $this->plot_area[1];
    if ($this->plot_max_x) {
      $this->SetTranslation();
    }
    return true;
  }

  function SetPlotAreaPixels($x1,$y1,$x2,$y2) {
    //Like in GD 0,0 is upper left
    if (!$this->x_tot_margin) {
      $this->SetMargins();
    }
    if ($x2 && $y2) {
      $this->plot_area = array($x1,$y1,$x2,$y2);
    } else {
      $this->plot_area = array($this->x_left_margin, $this->y_top_margin,
			       $this->image_width - $this->x_right_margin,
			       $this->image_height - $this->y_bot_margin
			       );
    }
    $this->plot_area_width = $this->plot_area[2] - $this->plot_area[0];
    $this->plot_area_height = $this->plot_area[3] - $this->plot_area[1];

    return true;

  }

  function SetPlotAreaWorld($xmin,$ymin,$xmax,$ymax) {
    if (($xmin == "")  && ($xmax == "")) {
      //For automatic setting of data we need $this->max_x
      if (!$this->max_y) {
	$this->FindDataLimits() ;
      }
      if ($this->data_type == 'text-data') { //labels for text-data is done at data drawing time for speed.
	$xmax = $this->max_x + 1 ;  //valid for BAR CHART TYPE GRAPHS ONLY
	$xmin = 0 ; 				//valid for BAR CHART TYPE GRAPHS ONLY
      } else {
	$xmax = $this->max_x * 1.02;
	$xmin = $this->min_x;
      }

      $ymax = ceil($this->max_y * 1.2);
      if ($this->min_y < 0) {
	$ymin = floor($this->min_y * 1.2);
      } else {
	$ymin = 0;
      }
    }

    $this->plot_min_x = $xmin;
    $this->plot_max_x = $xmax;

    if ($ymin == $ymax) {
      $ymax += 1;
    }
    if ($this->yscale_type == "log") { 
      //extra error checking
      if ($ymin <= 0) { 
	$ymin = 1;
      } 
      if ($ymax <= 0) { 
	$this->PrintError('Log plots need data greater than 0');
      }
    }
    $this->plot_min_y = $ymin;
    $this->plot_max_y = $ymax;

    if ($ymax <= $ymin) {
      $this->DrawError('Error in Data - max not gt min');
    }

    //Set the boundaries of the box for plotting in world coord
    //		if (!$this->x_tot_margin) { //We need to know the margins before we can calculate scale
    //			$this->SetMargins();
    //		}
    //For this we have to reset the scale
    if ($this->plot_area_width) {
      $this->SetTranslation();
    }

    return true;

  } //function SetPlotAreaWorld


  function PrintError($error_message) {
    // prints the error message to stdout and die
    echo "<p><b>Fatal error</b>: $error_message<p>";
    die;
  }

  function DrawError($error_message) {
    // prints the error message inline into
    // the generated image

    if (($this->img) == "") { $this->InitImage(); } ;

    $ypos = $this->image_height/2;

    if ($this->use_ttf == 1) {
      ImageRectangle($this->img, 0,0,$this->image_width,$this->image_height,ImageColorAllocate($this->img,255,255,255));
      ImageTTFText($this->img, $this->small_ttffont_size, 0, $xpos, $ypos, ImageColorAllocate($this->img,0,0,0), $this->axis_ttffont, $error_message);
    } else {
      ImageRectangle($this->img, 0,0,$this->image_width,$this->image_height,ImageColorAllocate($this->img,255,255,255));
      ImageString($this->img, $this->small_font,1,$ypos,$error_message, ImageColorAllocate($this->img,0,0,0));
    }

    $this->PrintImage();
    return true;
  }

  function TTFBBoxSize($size, $angle, $font, $string) {

    //Assume angle < 90
    $arr = ImageTTFBBox($size, 0, $font, $string);
    $flat_width  = $arr[0] - $arr[2];
    $flat_height = abs($arr[3] - $arr[5]);

    // for 90deg:
    //	$height = $arr[5] - $arr[7];
    //	$width = $arr[2] - $arr[4];

    $angle = deg2rad($angle);
    $width  = ceil(abs($flat_width*cos($angle) + $flat_height*sin($angle))); //Must be integer
    $height = ceil(abs($flat_width*sin($angle) + $flat_height*cos($angle))); //Must be integer

    return array($width, $height);
  }

  function SetXLabelHeight() {

    if ($this->use_ttf == 1) {
      //Space for the X Label
      $size = $this->TTFBBoxSize($this->x_label_ttffont_size, 0, $this->axis_ttffont, $this->x_label_txt);
      $tmp = $size[1];

      //$string = Str_Repeat('w', $this->x_datalabel_maxlength);
      $i = 0;
      $string = '';
      while ($i < $this->x_datalabel_maxlength) {
	$string .= 'w';
	$i++;
      }

      //Space for the axis data labels
      $size = $this->TTFBBoxSize($this->axis_ttffont_size, $this->x_datalabel_angle, $this->axis_ttffont, $string);

      $this->x_label_height = 2*$tmp + $size[1] + 4;

    } else {
      //For Non-TTF fonts we can have only angles 0 or 90
      if ($this->x_datalabel_angle == 90) {
	$this->x_label_height = $this->x_datalabel_maxlength * ImageFontWidth($this->small_font) / 1.5;
      } else {
	$this->x_label_height = 5 * ImageFontHeight($this->small_font);
      }
    }

    $this->SetMargins();

    return true;
  } //function SetXLabelHeight

  function SetYLabelWidth() {
    //$ylab = sprintf("%6.1f %s",$i,$si_units[0]);  //use for PHP2 compatibility
    //the "." is for space. It isn't actually printed
    $ylab = number_format($this->max_y, $this->y_precision, ".", ",") . $this->si_units . ".";

    if ($this->use_ttf == 1) {
      $size = $this->TTFBBoxSize($this->axis_ttffont_size, 0, $this->axis_ttffont, $ylab);
    } else {
      $size[0] = StrLen($ylab) * $this->small_font_width * .6;
    }

    $this->y_label_width = $size[0] * 2;
    //echo "SYLW: $this->y_label_width<br>";
    //exit;

    $this->SetMargins();
    return true;
  }

  function SetEqualXCoord() {
    //for plots that have equally spaced x variables and multiple bars per x-point.

    $space = ($this->plot_area[2] - $this->plot_area[0]) / ($this->number_x_points * 2) * $this->group_frac_width;
    $group_width = $space * 2;
    $bar_width = $group_width / $this->records_per_group;
    //I think that eventually this space variable will be replaced by just graphing x.
    $this->data_group_space = $space;
    $this->record_bar_width = $bar_width;
    return true;
  }

  function SetLabelScalePosition($which_blp) {
    //0 to 1
    $this->label_scale_position = $which_blp;
    return true;
  }

  function SetErrorBarSize($which_ebs) {
    //in pixels
    $this->error_bar_size = $which_ebs;
    return true;
  }

  function SetErrorBarShape($which_ebs) {
    //in pixels
    $this->error_bar_shape = $which_ebs;
    return true;
  }

  function SetPointShape($which_pt) {
    //in pixels
    $this->point_shape = $which_pt;
    return true;
  }

  function SetPointSize($which_ps) {
    //in pixels
    SetType($which_ps,'integer');
    $this->point_size = $which_ps;

    if ($this->point_shape == "diamond" or $this->point_shape == "triangle") {
      if ($this->point_size % 2 != 0) {
	$this->point_size++;
      }
    }
    return true;
  }

  function SetDataType($which_dt) {
    //The next three lines are for past compatibility.
    if ($which_dt == "text-linear") { $which_dt = "text-data"; };
    if ($which_dt == "linear-linear") { $which_dt = "data-data"; };
    if ($which_dt == "linear-linear-error") { $which_dt = "data-data-error"; };

    $this->data_type = $which_dt; //text-data, data-data, data-data-error
    return true;
  }

  function SetDataValues($which_dv)
  {
    $this->data_values = $which_dv;
    //echo $this->data_values
    return true;
  }

  //////////////COLORS
  function SetRGBArray ($which_color_array) { 
    if ( is_array($which_color_array) ) { 
      //User Defined Array
      $this->rgb_array = $which_color_array;
      return true;
    } elseif ($which_color_array == 2) { //Use the small predefined color array
      $this->rgb_array = array(
			       "white"			=> array(255, 255, 255),
			       "snow"			=> array(255, 250, 250),
			       "PeachPuff"		=> array(255, 218, 185),
			       "ivory"			=> array(255, 255, 240),
			       "lavender"		=> array(230, 230, 250),
			       "black"			=> array(  0,   0,   0),
			       "DimGrey"			=> array(105, 105, 105),
			       "gray"			=> array(190, 190, 190),
			       "grey"			=> array(190, 190, 190),
			       "navy"			=> array(  0,   0, 128),
			       "SlateBlue"			=> array(106,  90, 205),
			       "blue"			=> array(  0,   0, 255),
			       "SkyBlue"			=> array(135, 206, 235),
			       "cyan"			=> array(  0, 255, 255),
			       "DarkGreen"			=> array(  0, 100,   0),
			       "green"			=> array(  0, 255,   0),
			       "YellowGreen"			=> array(154, 205,  50),
			       "yellow"			=> array(255, 255,   0),
			       "orange"			=> array(255, 165,   0),
			       "gold"			=> array(255, 215,   0),
			       "peru"			=> array(205, 133,  63),
			       "beige"			=> array(245, 245, 220),
			       "wheat"			=> array(245, 222, 179),
			       "tan"			=> array(210, 180, 140),
			       "brown"			=> array(165,  42,  42),
			       "salmon"			=> array(250, 128, 114),
			       "red"			=> array(255,   0,   0),
			       "pink"			=> array(255, 192, 203),
			       "maroon"			=> array(176,  48,  96),
			       "magenta"			=> array(255,   0, 255),
			       "violet"			=> array(238, 130, 238),
			       "plum"			=> array(221, 160, 221),
			       "orchid"			=> array(218, 112, 214),
			       "purple"			=> array(160,  32, 240),
			       "azure1"			=> array(240, 255, 255),
			       "aquamarine1"		=> array(127, 255, 212)
			       );
      return true;
    } elseif ($which_color_array == 1)  { 
      include("./rgb.inc.php"); //Get large $ColorArray
      $this->rgb_array = $RGBArray;
    } else { 
      $this->rgb_array = array("white" =>array(255,255,255), "black" => array(0,0,0));
      exit;
    }

    return true;
  }

  function SetColor($which_color) { 
    //obsoleted by SetRGBColor
    SetRgbColor($which_color);
    return true;
  }

  function SetIndexColor($which_color) { //Color is passed in as anything
    list ($r, $g, $b) = $this->SetRgbColor($which_color);  //Translate to RGB
    $index = ImageColorExact($this->img, $r, $g, $b);
    if ($index == -1) {
      //return ImageColorAllocate($this->img, $r, $g, $b);
      //return ImageColorClosest($this->img, $r, $g, $b);
      return ImageColorResolve($this->img, $r, $g, $b); //requires PHP 3.0.2 and later
    } else {
      return $index;
    }
  }
	
  function SetTransparentColor($which_color) { 
    ImageColorTransparent($this->img,$this->SetIndexColor($which_color));
    return true;
  }

  function SetRgbColor($color_asked) {
    //Returns an array in R,G,B format 0-255
    if ($color_asked == "") { $color_asked = array(0,0,0); };

    if ( count($color_asked) == 3 ) { //already array of 3 rgb
      $ret_val =  $color_asked;
    } else { // is asking for a color by string
      if(substr($color_asked,0,1) == "#") {  //asking in #FFFFFF format. 
	$ret_val =  array(hexdec(substr($color_asked,1,2)), hexdec(substr($color_asked,3,2)), hexdec(substr($color,5,2)));
      } else { 
	$ret_val =  $this->rgb_array[$color_asked];
      }
    }
    return $ret_val;
  }

  function SetDataColors($which_data,$which_border) {
    //Set the data to be displayed in a particular color
    if (!$which_data) {
      $which_data = array(array(0,255,0),array(0,0,248),'yellow',array(255,0,0),'orange');
      $which_border = array('black');
    }

    $this->data_color = $which_data;  //an array
    $this->data_border_color = $which_border;  //an array

    unset($this->ndx_data_color);
    reset($this->data_color);  //data_color can be an array of colors, one for each thing plotted
    //while (list(, $col) = each($this->data_color)) 
    $i = 0;
    while (list(, $col) = each($which_data)) {
      $this->ndx_data_color[$i] = $this->SetIndexColor($col);
      $i++;
    }

    // border_color
    //If we are also going to put a border on the data (bars, dots, area, ...)
    //	then lets also set a border color as well.
    //foreach($this->data_border_color as $col) 
    unset($this->ndx_data_border_color);
    reset($this->data_border_color);
    $i = 0;
    while (list(, $col) = each($this->data_border_color)) {
      $this->ndx_data_border_color[$i] = $this->SetIndexColor($col);
      $i++;
    }

    //Set color of the error bars to be that of data if not already set. 
    if (!$this->error_bar_color) { 
      reset($which_data);
      $this->SetErrorBarColors($which_data);
    }

    return true;

  } //function SetDataColors

  function SetErrorBarColors($which_data) {

    //Set the data to be displayed in a particular color

    if ($which_data) {
      $this->error_bar_color = $which_data;  //an array
      unset($this->ndx_error_bar_color);
      reset($this->error_bar_color);  //data_color can be an array of colors, one for each thing plotted
      $i = 0;
      while (list(, $col) = each($this->error_bar_color)) {
	$this->ndx_error_bar_color[$i] = $this->SetIndexColor($col);
	$i++;
      }
      return true;
    }
    return false;
  } //function SetErrorBarColors


  function DrawPlotBorder() {
    switch ($this->plot_border_type) {
    case "left" :
      ImageLine($this->img, $this->plot_area[0],$this->ytr($this->plot_min_y),
		$this->plot_area[0],$this->ytr($this->plot_max_y),$this->ndx_grid_color);
      break;
    case "none":
      //Draw No Border
      break;
    default:
      ImageRectangle($this->img, $this->plot_area[0],$this->ytr($this->plot_min_y),
		     $this->plot_area[2],$this->ytr($this->plot_max_y),$this->ndx_grid_color);
      break;
    }
    $this->DrawYAxis();
    $this->DrawXAxis();
    return true;
  }


  function SetHorizTickIncrement($which_ti) {
    //Use either this or NumHorizTicks to set where to place x tick marks
    if ($which_ti) {
      $this->horiz_tick_increment = $which_ti;  //world coordinates
    } else {
      if (!$this->max_x) {
	$this->FindDataLimits();  //Get maxima and minima for scaling
      }
      //$this->horiz_tick_increment = ( ceil($this->max_x * 1.2) - floor($this->min_x * 1.2) )/10;
      $this->horiz_tick_increment =  ($this->plot_max_x  - $this->plot_min_x  )/10;
    }
    $this->num_horiz_ticks = ''; //either use num_vert_ticks or vert_tick_increment, not both
    return true;
  }

  function SetDrawVertTicks($which_dvt) {
    $this->draw_vert_ticks = $which_dvt;
    return true;
  } 

  function SetVertTickIncrement($which_ti)
  {
    //Use either this or NumVertTicks to set where to place y tick marks
    if ($which_ti)
      {
	$this->vert_tick_increment = $which_ti;  //world coordinates
      }
    else
      {
	if (!$this->max_y)
	  {
	    $this->FindDataLimits();  //Get maxima and minima for scaling
	  }
	$this->vert_tick_increment = ceil(( ceil($this->max_y * 1.2) - floor($this->min_y * 1.2) )/10);
	//$this->vert_tick_increment =  ceil(($this->plot_max_y  - $this->plot_min_y  )/10);
      }
    $this->num_vert_ticks = ''; //either use num_vert_ticks or vert_tick_increment, not both
    return true;
  }

  function SetNumHorizTicks($which_nt) {
    $this->num_horiz_ticks = $which_nt;
    $this->horiz_tick_increment = '';  //either use num_horiz_ticks or horiz_tick_increment, not both
    return true;
  }

  function SetNumVertTicks($which_nt)
  {
    $this->num_vert_ticks = $which_nt;
    $this->vert_tick_increment = '';  //either use num_vert_ticks or vert_tick_increment, not both
    return true;
  }
  function SetVertTickPosition($which_tp) {
    $this->vert_tick_position = $which_tp; //plotleft, plotright, both, yaxis
    return true;
  }
  function SetSkipBottomTick($which_sbt) {
    $this->skip_bottom_tick = $which_sbt;
    return true;
  }

  function SetTickLength($which_tl) {
    $this->tick_length = $which_tl;
    return true;
  }

  function DrawYAxis() { 
    //Draw Line at left side or at this->y_axis_position
    if ($this->y_axis_position != "") { 
      $yaxis_x = $this->xtr($this->y_axis_position);
    } else { 
      $yaxis_x = $this->plot_area[0];
    }

    ImageLine($this->img, $yaxis_x, $this->plot_area[1], 
	      $yaxis_x, $this->plot_area[3], $this->ndx_grid_color);
    //$yaxis_x, $this->plot_area[3], 9);

    if ($this->draw_vert_ticks == 1) { 
      $this->DrawVerticalTicks();
    }

  } //function DrawYAxis

  function DrawXAxis() {
    //Draw Tick and Label for Y axis
    $ylab =$this->FormatYTickLabel($this->x_axis_position);
    if ($this->skip_bottom_tick != 1) { 
      $this->DrawVerticalTick($ylab,$this->x_axis_position);
    }

    //Draw X Axis at Y=$x_axis_postion
    ImageLine($this->img,$this->plot_area[0]+1,$this->ytr($this->x_axis_position),
	      $this->xtr($this->plot_max_x)-1,$this->ytr($this->x_axis_position),$this->ndx_tick_color);

    //X Ticks and Labels
    if ($this->data_type != 'text-data') { //labels for text-data done at data drawing time for speed.
      $this->DrawHorizontalTicks();
    }
    return true;
  }

  function DrawHorizontalTicks() {
    //Ticks and lables are drawn on the left border of PlotArea.
    //Left Bottom
    ImageLine($this->img,$this->plot_area[0],
	      $this->plot_area[3]+$this->tick_length,
	      $this->plot_area[0],$this->plot_area[3],$this->ndx_tick_color);

    switch ($this->x_grid_label_type) {
    case "title":
      $xlab = $this->data_values[0][0];
      break;
    case "data":
      $xlab = number_format($this->plot_min_x,$this->x_precision,".",",") . "$this->si_units";
      break;
    case "none":
      $xlab = '';
      break;
    case "time":  //Time formatting suggested by Marlin Viss
      $xlab = strftime($this->x_time_format,$this->plot_min_x);
      break;
    default:
      //Unchanged from whatever format is passed in
      $xlab = $this->plot_min_x;
      break;
    }

    if ($this->x_datalabel_angle == 90) { 
      $xpos =  $this->plot_area[0] - $this->small_font_height/2;
      $ypos = ( $this->small_font_width*strlen($xlab) + $this->plot_area[3] + $this->small_font_height);
      ImageStringUp($this->img, $this->small_font,$xpos, $ypos, $xlab, $this->ndx_text_color);
    } else {
      $xpos = $this->plot_area[0] - $this->small_font_width*strlen($xlab)/2 ;
      $ypos = $this->plot_area[3] + $this->small_font_height;
      ImageString($this->img, $this->small_font,$xpos, $ypos, $xlab, $this->ndx_text_color);
    }

    //Will be changed to allow for TTF fonts in data as well.
    //$this->DrawText($this->small_font, $this->x_datalabel_angle, $xpos, $ypos, $this->ndx_title_color, '', $xlab); 

    //Top

    if ($this->horiz_tick_increment) {
      $delta_x = $this->horiz_tick_increment;
    } elseif ($this->num_horiz_ticks) {
      $delta_x = ($this->plot_max_x - $this->plot_min_x) / $this->num_horiz_ticks;
    } else {
      $delta_x =($this->plot_max_x - $this->plot_min_x) / 10 ;
    }

    $i = 0;
    $x_tmp = $this->plot_min_x;
    SetType($x_tmp,'double');

    while ($x_tmp <= $this->plot_max_x){
      //$xlab = sprintf("%6.1f %s",$min_x,$si_units[0]);  //PHP2 past compatibility
      switch ($this->x_grid_label_type) {
      case "title":
	$xlab = $this->data_values[$x_tmp][0];
	break;
      case "data":
	$xlab = number_format($x_tmp,$this->x_precision,".",",") . "$this->si_units";
	break;
      case "none":
	$xlab = '';
	break;
      case "time":  //Time formatting suggested by Marlin Viss
	$xlab = strftime($this->x_time_format,$x_tmp);
	break;
      default:
	//Unchanged from whatever format is passed in
	$xlab = $x_tmp;
	break;
      }

      $x_pixels = $this->xtr($x_tmp);

      //Bottom Tick
      ImageLine($this->img,$x_pixels,$this->plot_area[3] + $this->tick_length,
		$x_pixels,$this->plot_area[3], $this->ndx_tick_color);
      //Top Tick
      //ImageLine($this->img,($this->xtr($this->plot_max_x)+$this->tick_length),
      //	$y_pixels,$this->xtr($this->plot_max_x)-1,$y_pixels,$this->ndx_tick_color);

      if ($this->draw_x_grid == 1) {
	ImageLine($this->img,$x_pixels,$this->plot_area[1],
		  $x_pixels,$this->plot_area[3], $this->ndx_light_grid_color);
      }

      if ($this->x_datalabel_angle == 90) {  //Vertical Code Submitted by Marlin Viss
	ImageStringUp($this->img, $this->small_font,
		      ( $x_pixels - $this->small_font_height/2),
		      ( $this->small_font_width*strlen($xlab) + $this->plot_area[3] + $this->small_font_height),$xlab, $this->ndx_text_color);
      } else {
	ImageString($this->img, $this->small_font,
		    ( $x_pixels - $this->small_font_width*strlen($xlab)/2) ,
		    ( $this->small_font_height + $this->plot_area[3]),$xlab, $this->ndx_text_color);
      }

      $i++;
      $x_tmp += $delta_x;
    }

  } // function DrawHorizontalTicks

  function FormatYTickLabel($which_ylab) { 
    switch ($this->y_grid_label_type) {
    case "data":
      $ylab = number_format($which_ylab,$this->y_precision,".",",") . "$this->si_units";
      break;
    case "none":
      $ylab = '';
      break;
    case "time":
      $ylab = strftime($this->y_time_format,$which_ylab);
      break;
    case "right":
      //Make it right aligned
      //$ylab = str_pad($which_ylab,$this->y_label_width," ",STR_PAD_LEFT); //PHP4 only
      $sstr = "%".strlen($this->plot_max_y)."s";
      $ylab = sprintf($sstr,$which_ylab);
      break;
    default:
      //Unchanged from whatever format is passed in
      $ylab = $which_ylab;
      break;
    }

    return($ylab);

  } //function FormatYTickLabel

  function DrawVerticalTick($which_ylab,$which_ypos) {  //ylab in world coord.
    //Draw Just one Tick, called from DrawVerticalTicks
    //Ticks and datalables can be left of plot only, right of plot only, 
    //  both on the left and right of plot, or crossing a user defined Y-axis
    // 
    //Its faster to draw both left and right ticks at same time
    //  than first left and then right. 

    if ($this->y_axis_position != "") { 
      //Ticks and lables are drawn on the left border of yaxis
      $yaxis_x = $this->xtr($this->y_axis_position);
    } else { 
      //Ticks and lables are drawn on the left border of PlotArea.
      $yaxis_x = $this->plot_area[0];
    }

    $y_pixels = $this->ytr($which_ypos);

    //Lines Across the Plot Area
    if ($this->draw_y_grid == 1) {
      ImageLine($this->img,$this->plot_area[0]+1,$y_pixels,
		$this->plot_area[2]-1,$y_pixels,$this->ndx_light_grid_color);
    }

    //Ticks to the Left of the Plot Area
    if (($this->vert_tick_position == "plotleft") || ($this->vert_tick_position == "both") ) { 
      ImageLine($this->img,(-$this->tick_length+$yaxis_x),
		$y_pixels,$yaxis_x,
		$y_pixels, $this->ndx_tick_color);
    }

    //Ticks to the Right of the Plot Area
    if (($this->vert_tick_position == "plotright") || ($this->vert_tick_position == "both") ) { 
      ImageLine($this->img,($this->plot_area[2]+$this->tick_length),
		$y_pixels,$this->plot_area[2],
		$y_pixels,$this->ndx_tick_color);
    }

    //Ticks on the Y Axis 
    if (($this->vert_tick_position == "yaxis") ) { 
      ImageLine($this->img,($yaxis_x - $this->tick_length),
		$y_pixels,$yaxis_x,$y_pixels,$this->ndx_tick_color);
    }

    //DataLabel
    //ajo working
    //$this->DrawText($this->y_label_ttffont, 0,($yaxis_x - $this->y_label_width - $this->tick_length/2),
    //		$y_pixels, $this->ndx_text_color, $this->axis_ttffont_size, $which_ylab);
    ImageString($this->img, $this->small_font, ($yaxis_x - $this->y_label_width - $this->tick_length/2),
		( -($this->small_font_height/2.0) + $y_pixels),$which_ylab, $this->ndx_text_color);
  }

  function DrawVerticalTicks() {

    if ($this->skip_top_tick != 1) { //If tick increment doesn't hit the top 
      //Left Top
      //ImageLine($this->img,(-$this->tick_length+$this->xtr($this->plot_min_x)),
      //		$this->ytr($this->plot_max_y),$this->xtr($this->plot_min_x),$this->ytr($this->plot_max_y),$this->ndx_tick_color);
      //$ylab = $this->FormatYTickLabel($plot_max_y);

      //Right Top
      //ImageLine($this->img,($this->xtr($this->plot_max_x)+$this->tick_length),
      //		$this->ytr($this->plot_max_y),$this->xtr($this->plot_max_x-1),$this->ytr($this->plot_max_y),$this->ndx_tick_color);

      //Draw Grid Line at Top
      ImageLine($this->img,$this->plot_area[0]+1,$this->ytr($this->plot_max_y),
		$this->plot_area[2]-1,$this->ytr($this->plot_max_y),$this->ndx_light_grid_color);

    }

    if ($this->skip_bottom_tick != 1) { 
      //Right Bottom
      //ImageLine($this->img,($this->xtr($this->plot_max_x)+$this->tick_length),
      //		$this->ytr($this->plot_min_y),$this->xtr($this->plot_max_x),
      //		$this->ytr($this->plot_min_y),$this->ndx_tick_color);

      //Draw Grid Line at Bottom of Plot
      ImageLine($this->img,$this->xtr($this->plot_min_x)+1,$this->ytr($this->plot_min_y),
		$this->xtr($this->plot_max_x),$this->ytr($this->plot_min_y),$this->ndx_light_grid_color);
    }
		
    // maxy is always > miny so delta_y is always positive
    if ($this->vert_tick_increment)
      {
	$delta_y = $this->vert_tick_increment;
      }
    elseif($this->num_vert_ticks)
      {
	$delta_y = ($this->plot_max_y - $this->plot_min_y) / $this->num_vert_ticks;
      }
    else
      {
	$delta_y = ($this->plot_max_y - $this->plot_min_y) / 10 ;
      }

    $y_tmp = $this->plot_min_y;
    SetType($y_tmp,'double');
    if ($this->skip_bottom_tick == 1) { 
      $y_tmp += $delta_y;
    }

    while ($y_tmp <= $this->plot_max_y){
      //For log plots: 
      if (($this->yscale_type == "log") && ($this->plot_min_y == 1) && 
	  ($delta_y%10 == 0) && ($y_tmp == $this->plot_min_y)) { 
	$y_tmp = $y_tmp - 1; //Set first increment to 9 to get: 1,10,20,30,...
      }

      $ylab = $this->FormatYTickLabel($y_tmp);

      $this->DrawVerticalTick($ylab,$y_tmp);

      $y_tmp += $delta_y;
    }

    return true;

  } // function DrawVerticalTicks

  function SetTranslation() {
    if ($this->xscale_type == "log") { 
      $this->xscale = ($this->plot_area_width)/(log10($this->plot_max_x) - log10($this->plot_min_x));
    } else { 
      $this->xscale = ($this->plot_area_width)/($this->plot_max_x - $this->plot_min_x);
    }
    if ($this->yscale_type == "log") { 
      $this->yscale = ($this->plot_area_height)/(log10($this->plot_max_y) - log10($this->plot_min_y));
    } else { 
      $this->yscale = ($this->plot_area_height)/($this->plot_max_y - $this->plot_min_y);
    }

    // GD defines x=0 at left and y=0 at TOP so -/+ respectively
    if ($this->xscale_type == "log") { 
      $this->plot_origin_x = $this->plot_area[0] - ($this->xscale * log10($this->plot_min_x) );
    } else { 
      $this->plot_origin_x = $this->plot_area[0] - ($this->xscale * $this->plot_min_x);
    }
    if ($this->yscale_type == "log") { 
      $this->plot_origin_y = $this->plot_area[3] + ($this->yscale * log10($this->plot_min_y));
    } else { 
      $this->plot_origin_y = $this->plot_area[3] + ($this->yscale * $this->plot_min_y);
    }

    $this->scale_is_set = 1;
  } // function SetTranslation

  function xtr($x_world) {
    //Translate world coordinates into pixel coordinates
    //The pixel coordinates are those of the ENTIRE image, not just the plot_area
    //$x_pixels =  $this->x_left_margin + ($this->image_width - $this->x_tot_margin)*(($x_world - $this->plot_min_x) / ($this->plot_max_x - $this->plot_min_x)) ;
    //which with a little bit of math reduces to ...
    if ($this->xscale_type == "log") { 
      $x_pixels =  $this->plot_origin_x + log10($x_world) * $this->xscale ;
    } else { 
      $x_pixels =  $this->plot_origin_x + $x_world * $this->xscale ;
    }
    return($x_pixels);
  }

  function ytr($y_world) {
    // translate y world coord into pixel coord
    if ($this->yscale_type == "log") { 
      $y_pixels =  $this->plot_origin_y - log10($y_world) * $this->yscale ;  //minus because GD defines y=0 at top. doh!
    } else { 
      $y_pixels =  $this->plot_origin_y - $y_world * $this->yscale ;  
    }
    return ($y_pixels);
  }


  function DrawDataLabel($lab,$x_world,$y_world) {
    //Depreciated. Use DrawText Instead.
    //Data comes in in WORLD coordinates
    //Draw data label near actual data point
    //$y = $this->ytr($y_world) ;  //in pixels
    //$x = $this->xtr($x_world) ;
    //$this->DrawText($which_font,$which_angle,$which_xpos,$which_ypos,$which_color,$which_size,$which_text,$which_halign='left');
    if ($this->use_ttf) {
      //ajjjo
      $lab_size = $this->TTFBBoxSize($this->axis_ttffont_size, $this->x_datalabel_angle, $this->axis_ttffont, $lab); //An array
      $y = $this->ytr($y_world) - $lab_size[1] ;  //in pixels
      $x = $this->xtr($x_world) - $lab_size[0]/2;
      ImageTTFText($this->img, $this->axis_ttffont_size, $this->x_datalabel_angle, $x, $y, $this->ndx_text_color, $this->axis_ttffont, $lab);
    } else {
      $lab_size = array($this->small_font_width*StrLen($lab), $this->small_font_height*3);
      if ($this->x_datalabel_angle == 90) {
	$y = $this->ytr($y_world) - $this->small_font_width*StrLen($lab); //in pixels
	$x = $this->xtr($x_world) - $this->small_font_height;
	ImageStringUp($this->img, $this->small_font,$x, $y ,$lab, $this->ndx_text_color);
      } else {
	$y = $this->ytr($y_world) - $this->small_font_height; //in pixels
	$x = $this->xtr($x_world) - ($this->small_font_width*StrLen($lab))/2;
	ImageString($this->img, $this->small_font,$x, $y ,$lab, $this->ndx_text_color);
      }
    }

  }

  function DrawXDataLabel($xlab,$xpos) {
    //xpos comes in in PIXELS not in world coordinates.
    //Draw an x data label centered at xlab
    if ($this->use_ttf) {
      $xlab_size = $this->TTFBBoxSize($this->axis_ttffont_size,
				      $this->x_datalabel_angle, $this->axis_ttffont, $xlab); //An array
      $y = $this->plot_area[3] + $xlab_size[1] + 4;  //in pixels
      $x = $xpos - $xlab_size[0]/2;
      ImageTTFText($this->img, $this->axis_ttffont_size,
		   $this->x_datalabel_angle, $x, $y, $this->ndx_text_color, $this->axis_ttffont, $xlab);
    } else {
      $xlab_size = array(ImageFontWidth($this->axis_font)*StrLen($xlab), $this->small_font_height*3);
      if ($this->x_datalabel_angle == 90) {
	$y = $this->plot_area[3] + ImageFontWidth($this->axis_font)*StrLen($xlab); //in pixels
	$x = $xpos - ($this->small_font_height);
	ImageStringUp($this->img, $this->axis_font,$x, $y ,$xlab, $this->ndx_text_color);
      } else {
	$y = $this->plot_area[3] + ImageFontHeight($this->axis_font); //in pixels
	$x = $xpos - (ImageFontWidth($this->axis_font)*StrLen($xlab))/2;
	ImageString($this->img, $this->axis_font,$x, $y ,$xlab, $this->ndx_text_color);
      }
    }

  }

  function DrawPieChart() {
    //$pi = '3.14159265358979323846';
    $xpos = $this->plot_area[0] + $this->plot_area_width/2;
    $ypos = $this->plot_area[1] + $this->plot_area_height/2;
    $diameter = (min($this->plot_area_width, $this->plot_area_height)) ;
    $radius = $diameter/2;

    ImageArc($this->img, $xpos, $ypos, $diameter, $diameter, 0, 360, $this->ndx_grid_color);

    $total = 0;
    reset($this->data_values);
    $tmp = $this->number_x_points - 1;
    while (list($j, $row) = each($this->data_values)) {
      //Get sum of each type
      $color_index = 0;
      $i = 0;
      //foreach ($row as $v) 
      while (list($k, $v) = each($row)) {
	if ($k != 0) {
	  if ($j == 0) { 
	    $sumarr[$i] = $v;
	  } elseif ($j < $tmp) { 
	    $sumarr[$i] += $v;
	  } else { 
	    $sumarr[$i] += $v;
	    // NOTE!  sum > 0 to make pie charts
	    $sumarr[$i] = abs($sumarr[$i]); 
	    $total += $sumarr[$i];
	  }
	}
	$i++;
      }
    }

    $color_index = 0;
    $start_angle = 0;

    reset($sumarr);
    $end_angle = 0;
    while (list(, $val) = each($sumarr)) {
      if ($color_index >= count($this->ndx_data_color)) $color_index=0;  //data_color = array
      $label_txt = number_format(($val / $total * 100), $this->y_precision, ".", ",") . "%";
      $val = 360 * ($val / $total);

      $end_angle += $val;
      $mid_angle = $end_angle - ($val / 2);

      $slicecol = $this->ndx_data_color[$color_index];

      //Need this again for FillToBorder
      ImageArc($this->img, $xpos, $ypos, $diameter, $diameter, 0, 360, $this->ndx_grid_color);

      $out_x = $radius * cos(deg2rad($end_angle));
      $out_y = - $radius * sin(deg2rad($end_angle));

      $mid_x = $xpos + ($radius/2 * cos(deg2rad($mid_angle))) ;
      $mid_y = $ypos + (- $radius/2 * sin(deg2rad($mid_angle)));

      $label_x = $xpos + ($radius * cos(deg2rad($mid_angle))) * $this->label_scale_position;
      $label_y = $ypos + (- $radius * sin(deg2rad($mid_angle))) * $this->label_scale_position;

      $out_x = $xpos + $out_x;
      $out_y = $ypos + $out_y;

      ImageLine($this->img, $xpos, $ypos, $out_x, $out_y, $this->ndx_grid_color);
      //ImageLine($this->img, $xpos, $ypos, $label_x, $label_y, $this->ndx_grid_color);
      ImageFillToBorder($this->img, $mid_x, $mid_y, $this->ndx_grid_color, $slicecol);

      if ($this->use_ttf) {
	ImageTTFText($this->img, $this->axis_ttffont_size, 0, $label_x, $label_y, $this->ndx_grid_color, $this->axis_ttffont, $label_txt);
      } else {
	ImageString($this->img, $this->small_font, $label_x, $label_y, $label_txt, $this->ndx_grid_color);
      }

      $start_angle = $val;

      $color_index++;
    }

  }

  function DrawLinesError() {
    //Draw Lines with Error Bars - data comes in as array("title",x,y,error+,error-,y2,error2+,error2-,...);
    $start_lines = 0;

    reset($this->data_values);
    while (list(, $row) = each($this->data_values)) {
      $color_index = 0;
      $i = 0;

      while (list($key, $val) = each($row)) {
	//echo "$key, $i, $val<br>";
	if ($key == 0) {
	  $lab = $val;
	} elseif ($key == 1) {
	  $x_now = $val;
	  $x_now_pixels = $this->xtr($x_now); //Use a bit more memory to save 2N operations.
	} elseif ($key%3 == 2) {
	  $y_now = $val;
	  $y_now_pixels = $this->ytr($y_now);

	  //Draw Data Label
	  if ( $this->draw_data_labels == 1) {
	    $this->DrawDataLabel($lab,$x_now,$y_now);
	  }

	  if ($color_index >= count($this->ndx_data_color)) { $color_index=0;};
	  $barcol = $this->ndx_data_color[$color_index];
	  $error_barcol = $this->ndx_error_bar_color[$color_index];

	  //echo "start = $start_lines<br>";
	  if ($start_lines == 1) {
	    for ($width = 0; $width < $this->line_width; $width++) {
	      ImageLine($this->img, $x_now_pixels, $y_now_pixels + $width,
			$lastx[$i], $lasty[$i] + $width, $barcol);
	    }
	  }

	  $lastx[$i] = $x_now_pixels;
	  $lasty[$i] = $y_now_pixels;
	  $color_index++;
	  $i++;
	  $start_lines = 1;
	} elseif ($key%3 == 0) {
	  $this->DrawYErrorBar($x_now,$y_now,$val,$this->error_bar_shape,$error_barcol);
	} elseif ($key%3 == 1) {
	  $this->DrawYErrorBar($x_now,$y_now,-$val,$this->error_bar_shape,$error_barcol);
	}
      }
    }
  }

  function DrawDotsError() {
    //Draw Dots - data comes in as array("title",x,y,error+,error-,y2,error2+,error2-,...);
    reset($this->data_values);
    while (list(, $row) = each($this->data_values)) {
      $color_index = 0;
      //foreach ($row as $v) 
      while (list($key, $val) = each($row)) {
	if ($key == 0) {
	} elseif ($key == 1) {
	  $xpos = $val;
	} elseif ($key%3 == 2) {
	  if ($color_index >= count($this->ndx_data_color)) $color_index=0;
	  $barcol = $this->ndx_data_color[$color_index];
	  $error_barcol = $this->ndx_error_bar_color[$color_index];
	  $ypos = $val;

	  $color_index++;
	  $this->DrawDot($xpos,$ypos,$this->point_shape,$barcol);
	} elseif ($key%3 == 0) {
	  $this->DrawYErrorBar($xpos,$ypos,$val,$this->error_bar_shape,$error_barcol);
	} elseif ($key%3 == 1) {
	  $mine = $val ;
	  $this->DrawYErrorBar($xpos,$ypos,-$val,$this->error_bar_shape,$error_barcol);
	}
      }
    }

  }

  function DrawDots() {
    //Draw Dots - data comes in as array("title",x,y1,y2,y3,...);
    reset($this->data_values);
    while (list($j, $row) = each($this->data_values)) {
      $color_index = 0;
      //foreach ($row as $v) 
      while (list($k, $v) = each($row)) {
	if ($k == 0) {
	} elseif (($k == 1) && ($this->data_type == "data-data"))  { 
	  $xpos = $v;
	} else {
	  if ($this->data_type == "text-data") { 
	    $xpos = ($j+.5); 
	  } 
	  if ($color_index >= count($this->ndx_data_color)) $color_index=0;
	  $barcol = $this->ndx_data_color[$color_index];

	  //if (is_numeric($v))  //PHP4 only
	  if ((strval($v) != "") ) {   //Allow for missing Y data 
	    $this->DrawDot($xpos,$v,$this->point_shape,$barcol);
	  }
	  $color_index++;
	}
      }
    }

  } //function DrawDots

  function DrawDotSeries() {
    //Depreciated: Use DrawDots
    $this->DrawDots();
  }

  function DrawThinBarLines() {
    //A clean,fast routine for when you just want charts like stock volume charts
    //Data must be text-data since I didn't see a graphing need for equally spaced thin lines. 
    //If you want it - then write to afan@jeo.net and I might add it. 

    if ($this->data_type != "data-data") { $this->DrawError('Data Type for ThinBarLines must be data-data'); };
    $y1 = $this->ytr($this->x_axis_position);

    reset($this->data_values);
    while (list(, $row) = each($this->data_values)) {
      $color_index = 0;
      while (list($k, $v) = each($row)) {
	if ($k == 0) {
	  $xlab = $v;
	} elseif ($k == 1) {
	  $xpos = $this->xtr($v);
	  if ( ($this->draw_x_data_labels == 1) )  { //See "labels_note1 above.
	    $this->DrawXDataLabel($xlab,$xpos);
	  }
	} else {
	  if ($color_index >= count($this->ndx_data_color)) $color_index=0;
	  $barcol = $this->ndx_data_color[$color_index];

	  ImageLine($this->img,$xpos,$y1,$xpos,$this->ytr($v),$barcol);
	  $color_index++;
	}
      }
    }

  }  //function DrawThinBarLines

  function DrawYErrorBar($x_world,$y_world,$error_height,$error_bar_type,$color) {
    $x1 = $this->xtr($x_world);
    $y1 = $this->ytr($y_world);
    $y2 = $this->ytr($y_world+$error_height) ;

    for ($width = 0; $width < $this->error_bar_line_width; $width++) {
      ImageLine($this->img, $x1+$width, $y1 , $x1+$width, $y2, $color);
      ImageLine($this->img, $x1-$width, $y1 , $x1-$width, $y2, $color);
    }
    switch ($error_bar_type) {
    case "line":
      break;
    case "tee":
      ImageLine($this->img, $x1-$this->error_bar_size, $y2, $x1+$this->error_bar_size, $y2, $color);
      break;
    default:
      ImageLine($this->img, $x1-$this->error_bar_size, $y2, $x1+$this->error_bar_size, $y2, $color);
      break;
    }
    return true;
  }

  function DrawDot($x_world,$y_world,$dot_type,$color) {
    $half_point = $this->point_size / 2;
    $x1 = $this->xtr($x_world) - $half_point;
    $x2 = $this->xtr($x_world) + $half_point;
    $y1 = $this->ytr($y_world) - $half_point;
    $y2 = $this->ytr($y_world) + $half_point;

    switch ($dot_type) {
    case "halfline":
      ImageFilledRectangle($this->img, $x1, $this->ytr($y_world), $this->xtr($x_world), $this->ytr($y_world), $color);
      break;
    case "line":
      ImageFilledRectangle($this->img, $x1, $this->ytr($y_world), $x2, $this->ytr($y_world), $color);
      break;
    case "rect":
      ImageFilledRectangle($this->img, $x1, $y1, $x2, $y2, $color);
      break;
    case "circle":
      ImageArc($this->img, $x1 + $half_point, $y1 + $half_point, $this->point_size, $this->point_size, 0, 360, $color);
      break;
    case "dot":
      ImageArc($this->img, $x1 + $half_point, $y1 + $half_point, $this->point_size, $this->point_size, 0, 360, $color);
      ImageFillToBorder($this->img, $x1 + $half_point, $y1 + $half_point, $color, $color);
      break;
    case "diamond":

      $arrpoints = array(
			 $x1,$y1 + $half_point,
			 $x1 + $half_point, $y1,
			 $x2,$y1 + $half_point,
			 $x1 + $half_point, $y2
			 );

      ImageFilledPolygon($this->img, $arrpoints, 4, $color);
      break;
    case "triangle":
      $arrpoints = array( $x1, $y1 + $half_point,
			  $x2, $y1 + $half_point,
			  $x1 + $half_point, $y2
			  );
      ImageFilledPolygon($this->img, $arrpoints, 3, $color);
      break;
    default:
      ImageFilledRectangle($this->img, $x1, $y1, $x2, $y2, $color);
      break;
    }
    return true;
  }

  function SetErrorBarLineWidth($which_seblw) {
    $this->error_bar_line_width = $which_seblw;
    return true;
  }


  function SetLineWidth($which_lw) {
    $this->line_width = $which_lw;
    if (!$this->error_bar_line_width) { 
      $this->error_bar_line_width = $which_lw;
    }
    return true;
  }

  function DrawArea() {
    //Data comes in as $data[]=("title",x,y,...);
    //Set first and last datapoints of area
    $i = 0;
    while ($i < $this->records_per_group) {
      $posarr[$i][] =  $this->xtr($this->min_x);	//x initial
      $posarr[$i][] =  $this->ytr($this->x_axis_position); 	//y initial
      $i++;
    }

    reset($this->data_values);
    while (list($j, $row) = each($this->data_values)) {
      $color_index = 0;
      //foreach ($row as $v)
      while (list($k, $v) = each($row)) {
	if ($k == 0) {
	  //Draw Data Labels
	  $xlab = SubStr($v,0,$this->x_datalabel_maxlength);
	} elseif ($k == 1) {
	  $x = $this->xtr($v);
	  // DrawXDataLabel interferes with Numbers on x-axis
	  //$this->DrawXDataLabel($xlab,$x);
	} else {
	  // Create Array of points for later

	  $y = $this->ytr($v);
	  $posarr[$color_index][] = $x;
	  $posarr[$color_index][] = $y;
	  $color_index++;
	}
      }
    }

    //Final_points
    for ($i = 0; $i < $this->records_per_group; $i++) {
      $posarr[$i][] =  $this->xtr($this->max_x);			//x final
      $posarr[$i][] =  $this->ytr($this->x_axis_position); 	//y final
    }

    $color_index=0;

    //foreach($posarr as $row)
    reset($posarr);
    while (list(, $row) = each($posarr)) {
      if ($color_index >= count($this->ndx_data_color)) $color_index=0;
      $barcol = $this->ndx_data_color[$color_index];
      //echo "$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[12], $barcol<br>";
      ImageFilledPolygon($this->img, $row, (count($row)) / 2, $barcol);
      $color_index++;
    }
    //exit;

  }

  function DrawAreaSeries() {

    //Set first and last datapoints of area
    $i = 0;
    while ($i < $this->records_per_group) {
      $posarr[$i][] =  $this->xtr(.5);			//x initial
      $posarr[$i][] =  $this->ytr($this->x_axis_position); 	//y initial
      $i++;
    }

    reset($this->data_values);
    while (list($j, $row) = each($this->data_values)) {
      $color_index = 0;
      //foreach ($row as $v)
      while (list($k, $v) = each($row)) {
	if ($k == 0) {
	  //Draw Data Labels
	  $xlab = SubStr($v,0,$this->x_datalabel_maxlength);
	  $this->DrawXDataLabel($xlab,$this->xtr($j + .5));
	} else {
	  // Create Array of points for later

	  $x = round($this->xtr($j + .5 ));
	  $y = round($this->ytr($v));
	  $posarr[$color_index][] = $x;
	  $posarr[$color_index][] = $y;
	  $color_index++;
	}
      }
    }

    //Final_points
    for ($i = 0; $i < $this->records_per_group; $i++) {
      $posarr[$i][] =  round($this->xtr($this->max_x + .5));	//x final
      $posarr[$i][] =  $this->ytr($this->x_axis_position); 		//y final
    }

    $color_index=0;

    //foreach($posarr as $row)
    reset($posarr);
    while (list(, $row) = each($posarr)) {
      if ($color_index >= count($this->ndx_data_color)) $color_index=0;
      $barcol = $this->ndx_data_color[$color_index];
      //echo "$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[12], $barcol<br>";
      ImageFilledPolygon($this->img, $row, (count($row)) / 2, $barcol);
      $color_index++;
    }

  }

  function DrawLines() {
    //Data comes in as $data[]=("title",x,y,...);
    $start_lines = 0;
    if ($this->data_type == "text-data") { 
      $lastx[0] = $this->xtr(0);
      $lasty[0] = $this->xtr(0);
    }

    //foreach ($this->data_values as $row)
    reset($this->data_values);
    while (list($j, $row) = each($this->data_values)) {

      $color_index = 0;
      $i = 0; 
      //foreach ($row as $v)
      while (list($k, $v) = each($row)) {
	if ($k == 0) { 
	  $xlab = SubStr($v,0,$this->x_datalabel_maxlength);
	} elseif (($k == 1) && ($this->data_type == "data-data"))  { 
	  $x_now = $this->xtr($v);
	} else {
	  //(double) $v;
	  // Draw Lines
	  if ($this->data_type == "text-data") { 
	    $x_now = $this->xtr($j+.5); 
	  } 

	  //if (is_numeric($v))  //PHP4 only
	  if ((strval($v) != "") ) {   //Allow for missing Y data 
	    $y_now = $this->ytr($v);
	    if ($color_index >= count($this->ndx_data_color)) { $color_index=0;} ;
	    $barcol = $this->ndx_data_color[$color_index];

	    if ($start_lines == 1) {
	      for ($width = 0; $width < $this->line_width; $width++) {
		if ($this->line_style[$i] == "dashed") {
		  $this->DrawDashedLine($x_now, $y_now + $width, $lastx[$i], $lasty[$i] + $width, 4,4, $barcol);
		} else {
		  ImageLine($this->img, $x_now, $y_now + $width, $lastx[$i], $lasty[$i] + $width, $barcol);
		}
	      }
	    }
	    $lastx[$i] = $x_now;
	  } else { 
	    $y_now = $lasty[$i];
	    //Don't increment lastx[$i]
	  }
	  //$bordercol = $this->ndx_data_border_color[$colbarcount];

	  $lasty[$i] = $y_now;
	  $color_index++;
	  $i++;
	}
	//Now we are assured an x_value
	if ( ($this->draw_x_data_labels == 1) && ($k == 1) )  { //See "labels_note1 above.
	  $this->DrawXDataLabel($xlab,$x_now);
	}
      } //while rows of data
      $start_lines = 1;
    }
  }

  //Data comes in as $data[]=("title",x,y,e+,e-,y2,e2+,e2-,...);

  function DrawLineSeries() {
    //This function is replaced by DrawLines
    //Tests have shown not much improvement in speed by having separate routines for DrawLineSeries and DrawLines
    //For ease of programming I have combined them
    return false;
  } //function DrawLineSeries

  function DrawDashedLine($x1pix,$y1pix,$x2pix,$y2pix,$dash_length,$dash_space,$color) {
    //Code based on work by Ariel Garza and James Pine
    //I've decided to have this be in pixels only as a replacement for ImageLine
    //$x1pix = $this->xtr($x1);
    //$y1pix = $this->ytr($y1);
    //$x2pix = $this->xtr($x2);
    //$y2pix = $this->ytr($y2);

    // Get the length of the line in pixels
    $line_length = ceil (sqrt(pow(($x2pix - $x1pix),2) + pow(($y2pix - $y1pix),2)) );

    $dx = ($x2pix - $x1pix) / $line_length;
    $dy = ($y2pix - $y1pix) / $line_length;
    $lastx	= $x1pix;
    $lasty	= $y1pix;

    // Draw the dashed line
    for ($i = 0; $i < $line_length; $i += ($dash_length + $dash_space)) {
      $xpix = ($dash_length * $dx) + $lastx;
      $ypix = ($dash_length * $dy) + $lasty;

      ImageLine($this->img,$lastx,$lasty,$xpix,$ypix,$color);
      $lastx = $xpix + ($dash_space * $dx);
      $lasty = $ypix + ($dash_space * $dy);
    }
  } // function DrawDashedLine

  function DrawBars() {

    if ($this->data_type != "text-data") { 
      $this->DrawError('Bar plots must be text-data: use function SetDataType("text-data")');
    }

    $xadjust = ($this->records_per_group * $this->record_bar_width )/4;

    reset($this->data_values);
    while (list($j, $row) = each($this->data_values)) {

      $color_index = 0;
      $colbarcount = 0;
      $x_now = $this->xtr($j+.5);

      while (list($k, $v) = each($row)) {
	if ($k == 0) {
	  //Draw Data Labels
	  $xlab = SubStr($v,0,$this->x_datalabel_maxlength);
	  $this->DrawXDataLabel($xlab,$x_now);
	} else {
	  // Draw Bars ($v)
	  $x1 = $x_now - $this->data_group_space + ($k-1)*$this->record_bar_width;
	  $x2 = $x1 + $this->record_bar_width*$this->bar_width_adjust; 

	  if ($v < $this->x_axis_position) {
	    $y1 = $this->ytr($this->x_axis_position);
	    $y2 = $this->ytr($v);
	  } else {
	    $y1 = $this->ytr($v);
	    $y2 = $this->ytr($this->x_axis_position);
	  }

	  if ($color_index >= count($this->ndx_data_color)) $color_index=0;
	  if ($colbarcount >= count($this->ndx_data_border_color)) $colbarcount=0;
	  $barcol = $this->ndx_data_color[$color_index];
	  $bordercol = $this->ndx_data_border_color[$colbarcount];

	  if ((strval($v) != "") ) {   //Allow for missing Y data 
	    if ($this->shading > 0) {
	      for($i=0;$i<($this->shading);$i++) { 
		//Shading set in SetDefaultColors
		ImageFilledRectangle($this->img, $x1+$i, $y1-$i, $x2+$i, $y2-$i, $this->ndx_i_light);
	      }
	    }

	    ImageFilledRectangle($this->img, $x1, $y1, $x2, $y2, $barcol);
	    ImageRectangle($this->img, $x1, $y1, $x2, $y2, $bordercol);
	    if ($this->draw_data_labels == '1') {  //ajo
	      $y1 = $this->ytr($this->label_scale_position * $v);
	      //$this->DrawDataLabel($v,$j + .5,$v*$this->label_scale_position);
	      $this->DrawText($this->x_label_ttffont, $this->x_label_angle,
			      $x1+$this->record_bar_width/2, $y1, $this->ndx_label_color, $this->x_label_ttffont_size, $v,'center','top');
	    }
	  } 

	  $color_index++;
	  $colbarcount++;
	}
      }
    }
  } //function DrawBars

  function DrawLegend($which_x1,$which_y1,$which_boxtype) {
    //Base code submitted by Marlin Viss
    $max_legend_length=0;
    reset($this->legend);
    while (list(,$leg) = each($this->legend)) {
      $len = strlen($leg);
      if ($max_legend_length < $len) {
	$max_legend_length = $len;
      }
    }

    $line_spacing = 1.25;
    $vert_margin = $this->small_font_height/2 ;
    $dot_height = $this->small_font_height*$line_spacing - 1;

    //Upper Left
    if ((!$which_x1) || (!$which_y1) ) {
      $box_start_x = $this->plot_area[2] - $this->small_font_width*($max_legend_length+4);
      $box_start_y = $this->plot_area[1] + 4;
    } else { 
      $box_start_x = $which_x1;
      $box_start_y = $which_y1;
    }

    //Lower Right
    $box_end_y = $box_start_y + $this->small_font_height*(count($this->legend)+1) + 2*$vert_margin; 
    //$box_end_x = $this->plot_area[2] - 5;
    $box_end_x = $box_start_x + $this->small_font_width*($max_legend_length+4) - 5;


    // Draw box for legend
    ImageFilledRectangle($this->img,
			 $box_start_x, $box_start_y,$box_end_x,
			 $box_end_y, $this->ndx_bg_color);
    ImageRectangle($this->img,
		   $box_start_x, $box_start_y,$box_end_x,
		   $box_end_y, $this->ndx_grid_color);

    $color_index=0;
    $i = 0;


    reset($this->legend);


    while (list(,$leg) = each($this->legend)) {
      $y_pos = $box_start_y + $this->small_font_height*($i)*($line_spacing) + $vert_margin;

      ImageString($this->img, $this->small_font,
		  $box_start_x + $this->small_font_width*( $max_legend_length - strlen($leg) + 1 ) ,
		  $y_pos,
		  $leg, $this->ndx_text_color);

      if ($color_index >= count($this->ndx_data_color)) $color_index=0;
      // Draw a box in the data color
      ImageFilledRectangle($this->img,
			   $box_end_x - $this->small_font_width*2,
			   $y_pos + 1, $box_end_x - $this->small_font_width,
			   $y_pos + $dot_height,
			   $this->ndx_data_color[$color_index]);

      ImageRectangle($this->img,
		     $box_end_x - $this->small_font_width*2,
		     $y_pos + 1, $box_end_x - $this->small_font_width,
		     $y_pos + $dot_height,
		     $this->ndx_text_color);
      $i++;
      $color_index++;
    }
  } //function DrawLegend


  function DrawGraph() {

    if (($this->img) == "") {
      $this->DrawError('No Image Defined: DrawGraph');
      //$this->PHPlot();
    }

    if (! is_array($this->data_values)) {
      $this->DrawBackground();
      $this->DrawError("No array of data in \$data_values");
    } else {
      if (!$this->data_color) {
	$this->SetDataColors(array('blue','green','yellow','red','orange','blue'),array('black'));
      }

      $this->FindDataLimits();  //Get maxima and minima for scaling

      $this->SetXLabelHeight();		//Get data for bottom margin

      $this->SetYLabelWidth();		//Get data for left margin

      if (!$this->plot_area_width) {
	$this->SetPlotAreaPixels('','','','');		//Set Margins
      }

      if (!$this->plot_max_y) {  //If not set by user call SetPlotAreaWorld,
	$this->SetPlotAreaWorld('','','','');
      }

      if ($this->data_type == "text-data") {
	$this->SetEqualXCoord();
      }

      $this->SetPointSize($this->point_size);

      $this->DrawBackground();
      $this->DrawImageBorder();

      $this->SetTranslation();

      if ($this->draw_plot_area_background == 1) {
	$this->DrawPlotAreaBackground();
      }
      //$foo = "$this->max_y, $this->min_y, $new_miny, $new_maxy, $this->x_label_height";
      //ImageString($this->img, 4, 20, 20, $foo, $this->ndx_text_color);

      switch ($this->plot_type)
	{
	case "bars":
	  $this->DrawPlotBorder();
	  $this->DrawLabels();
	  $this->DrawBars();
	  $this->DrawXAxis();
	  break;
	case "thinbarline":
	  $this->DrawPlotBorder();
	  $this->DrawLabels();
	  $this->DrawThinBarLines();
	  break;
	case "lines":
	  $this->DrawPlotBorder();
	  $this->DrawLabels();
	  if ( $this->data_type == "text-data") {
	    $this->DrawLines();
	  } elseif ( $this->data_type == "data-data-error") {
	    $this->DrawLinesError();
	  } else {
	    $this->DrawLines();
	  }
	  break;
	case "area":
	  $this->DrawPlotBorder();
	  $this->DrawLabels();
	  if ( $this->data_type == "text-data") {
	    $this->DrawAreaSeries();
	  } else {
	    $this->DrawArea();
	  }
	  break;
	case "linepoints":
	  $this->DrawPlotBorder();
	  $this->DrawLabels();
	  if ( $this->data_type == "text-data") {
	    $this->DrawLines();
	    $this->DrawDots();
	  } elseif ( $this->data_type == "data-data-error") {
	    $this->DrawLinesError();
	    $this->DrawDotsError();
	  } else {
	    $this->DrawLines();
	    $this->DrawDots();
	  }
	  break;
	  case "points";
	  $this->DrawPlotBorder();
	  $this->DrawLabels();
	  if ( $this->data_type == "text-data") {
	    $this->DrawDots();
	  } elseif ( $this->data_type == "data-data-error") {
	    $this->DrawDotsError();
	  } else {
	    $this->DrawDots();
	  }
	  break;
	case "pie":
	  $this->DrawPieChart();
	  $this->DrawLabels();
	  break;
	default:
	  $this->DrawPlotBorder();
	  $this->DrawLabels();
	  $this->DrawBars();
	  break;
	}

      if ($this->legend) {
	$this->DrawLegend($this->legend_x_pos,$this->legend_y_pos,'');
      }

    }
    if ($this->print_image == 1)
      { 
      $this->PrintImage();
    }
  } //function DrawGraph

}

// $graph = new PHPlot;

// $graph->DrawGraph();

?>
