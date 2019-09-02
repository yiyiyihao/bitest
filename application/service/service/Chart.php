<?php
/**
 * 图表数据类
 * 基于Echart 4.1.0版本完整版扩展编写,只覆盖当前使用范围,如需扩展使用,请自行修改扩展
 * @author chany
 * @date 2018-09-13
 */
namespace app\service\service;

class Chart{
    //要返回的图表配置数据集
    protected $option   = [];
    protected $legend   = [];
    protected $xAxis    = [];
    protected $yAxis    = [];
    protected $series   = [];
    
    //初始化赋值
    public function __construct($type = false,$legend = NULL,$chartName = NULL,$chartData = NULL,$color = NULL,$legendShow = TRUE,$xname = '',$yname = ''){
        $color = $color ? $color : ['rgba(225,85,85,0.8)'];
        //初始化设置tooltip
//         $this->option['tooltip']['formatter']     = "{a} <br/>{b} : {c} ({d}%)";
        if($type){
            $this->type($type,$legend,$chartName,$chartData,$color,$legendShow,$xname,$yname);
        }
    }
    
    /**
     * 配置x轴数据
     * $type string value:可用于计算的连续数值 category:无关联的独立数据 time:时间轴
     */
    public function xAxis($data = [],$type = 'category',$boundaryGap = false,$axisLine = [],$xname=''){
        $xAxis = [];
        $xAxis['type']        =   $type;
        $xAxis['name']        =   $xname;
        $xAxis['nameTextStyle']        =   [
            'padding'      => [0,0,0,-10],
            'color'        => 'white'
        ];
        if($boundaryGap) $xAxis['boundaryGap'] =   $boundaryGap;
        $xAxis['data']        =   $data;
        $xAxis['axisTick']    =   ['alignWithLabel'=>true];
        $xAxis['axisLine']    =   !empty($axisLine) ? $axisLine : $this->normalAxisLine();
        $xAxis['axisLabel']   =   [
            "textStyle" => [
                "color" => '#ccc'
            ]
        ];
        $this->xAxis[] = $xAxis;
        return $this;
    }
    
    /**
     * 配置y轴数据
     */
    public function yAxis($type = 'value',$axisLine = [],$yname=''){
        $yAxis = [];
        $yAxis['type']        =   $type;
        $yAxis['name']        =   $yname;
        $yAxis['minInterval']      =  1;
        $yAxis['nameTextStyle']        =   [
            'padding'      => [0,0,0,50],
            'color'         => 'white'
        ];
        $yAxis['axisLine']    =   !empty($axisLine) ? $axisLine : $this->normalAxisLine(false);
        $yAxis['axisLabel']   =   [
            "textStyle" => [
                "color" => '#ccc'
            ]
        ];
        $this->yAxis[] = $yAxis;
        return $this;
    }
    
    /**
     * 赋值数据
     */
    public function series($type = '',$name = FALSE,$data = [],$show = true, $smooth = 0){
        if(empty($type)) return ;
        $series = [
            'type'          =>  $type,
            'name'          =>  $name,
            'data'          =>  $data,
        ];
        switch ($type){
            case 'line':
                $series['smooth']   =  $smooth;
                /*$series['itemStyle']= [
                    'normal'    => ["areaStyle"=>'default']
                ];*/
                break;
            case 'bar':
                $series['barWidth']     = '40%'; 
                $series['label']        = [
                    'show'          =>  $show,
                    'position'      =>  'top',
                ];
                break;
            case 'pie':
                $series['radius'] = ['50%', '70%'];
                $series['label'] = [
                    'normal'    =>  [
                        'show'  => false,
                    ],
                ];
                break;
            default:
                break;
        }
        $this->series[] = $series;
        return $this;
    }
    
    /**
     * 赋值基础类型属性
     */
    public function type($type,$legend = [],$chartName = [],$chartData = [],$color = [],$legendShow = TRUE,$xname,$yname){
        switch ($type){
            case 'bar':
                self::setOption('color',$color);
                self::setOption('tooltip',[
                    'trigger'       =>  'axis',
                    'axisPointer'   =>  [
                        'type'      =>  'shadow',
                    ],
                ]);
                self::setOption('grid',[
                    'top'           =>  '30%',
                    'left'          =>  '3%',
                    'right'         =>  '10%',
                    'bottom'        =>  '3%',
                    'containLabel'  =>  true,
                ]);
//                self::xAxis($legend);
//                self::yAxis();
                self::xAxis($legend,'category',false,false,$xname);
                self::yAxis('value',false,$yname);
                self::series($type,$chartName,$chartData);
                break;
            case 'pie':
                self::setOption('color',$color);
                self::setOption('tooltip',[
                    'formatter'       =>  '{a} <br/>{b} : {c} ({d}%)',
                ]);
                self::setOption('legend',[
                    'orient'        =>  'vertical',
				    'left'          =>  'left',
				    'data'          =>  $legend,
                    'textStyle'     =>  [
                        'color' =>  '#ccc',
                    ],
                ]);
                self::series($type,$chartName,$chartData,false);
                break;
            case 'line':
                break;
            case 'radar':
                break;
            case 'group':
                self::setOption('color',$color);
                self::setOption('tooltip',[
                    'trigger'       =>  'axis',
                ]);
                self::setOption('legend',[
                    'textStyle'     =>  ['color'=>['#ccc']],
                    'left'          =>  'left',
                    'data'          =>  $legend,
                    'show'          =>  $legendShow,
                ]);
                self::setOption('grid',[
                    'left'          =>  '3%',
                    'right'         =>  '8%',
                    'bottom'        =>  '3%',
                    'containLabel'  =>  true,
                ]);
                self::setOption('lineStyle',[
                    'color' => $color,
                ]);
                self::xAxis($chartName,$type = 'category',$boundaryGap = false,$axisLine = [],$xname);
                self::yAxis($type = 'value',$axisLine = [],$yname);
                foreach ($chartData as $k=>$v){
                    self::series($v['type'],$v['name'],$v['data'], TRUE, $v['smooth']);
                }
                break;
        }
        return $this;
    }

    /**
     * 设置option
     */
    public function setOption($name= null,$option = []){
        if(empty($name) || empty($option)) return ;
        if( $name != null && isset($this->option[$name]) ){//如果该参数已经定义,则合并新旧参数,相同键值使用最新
            $this->option[$name] = array_merge($this->option[$name],$option);
        }else{
            $this->option[$name] = $option;
        }
        return $this;
    }
    
    /**
     * 取得option
     */
    public function getOption() {
        //扩展使用默认必须资源
        $this->trimOption();
//         return json_encode($this->option);
        return $this->option;
    }
    //以下为私有方法
    /**
     * 整理option各个资源项
     */
    private function trimOption(){
        if(!empty($this->title))    $this->option['title']      = $this->title;
        if(!empty($this->legend))   $this->option['legend']     = $this->legend;
        if(!empty($this->tooltip))  $this->option['tooltip']    = $this->tooltip;
        if(!empty($this->grid))     $this->option['grid']       = $this->gritimedifftimediffd;
        if(!empty($this->xAxis))    $this->option['xAxis']      = $this->xAxis;
        if(!empty($this->yAxis))    $this->option['yAxis']      = $this->yAxis;
        if(!empty($this->series))   $this->option['series']     = $this->series;
    }
    
    /**
     * 默认图表颜色样式
     */
    private function normalAxisLine($show = true,$color = ['#eee']){
        $data = [
            "lineStyle"         =>      [
                'color'     => $color,
            ],
//             "show"              =>      $show,
        ];
        return $data;
    }
    
}
