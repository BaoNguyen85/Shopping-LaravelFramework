<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feeship;
use App\Models\Shipping;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Customer;
use App\Models\Coupon;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use App\Models\Product;
use App\Models\Statistic;

class OrderController extends Controller
{
    public function print_order($checkout_code){
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML($this->print_order_convert($checkout_code));
        return $pdf->stream();
    }
    public function print_order_convert($checkout_code){
        $order_details = OrderDetails::where('order_code',$checkout_code)->get();
        $order = Order::where('order_code',$checkout_code)->get();
        foreach($order as $key => $ord){
            $customer_id = $ord->customer_id;
            $shipping_id = $ord->shipping_id;
            
        }
        $customer = Customer::where('customer_id',$customer_id)->first();
        $shipping = Shipping::where('shipping_id',$shipping_id)->first();
        $order_details_product = OrderDetails::with('product')->where('order_code',$checkout_code)->get();
        
        foreach($order_details_product as $key => $order_d){
            $product_coupon = $order_d->product_coupon;
            
        }

        if($product_coupon != 'no'){
            $coupon = Coupon::where('coupon_code',$product_coupon)->first();
            
            $coupon_condition = $coupon->coupon_condition;
            $coupon_number = $coupon->coupon_number;
            if($coupon_condition==1){
                $coupon_echo = $coupon_number.'%';
            }elseif($coupon_condition==2){
                $coupon_echo = number_format($coupon_number,0,',','.').'đ';
            }

        }else{
            $coupon_condition = 2;
            $coupon_number = 0;
            
            $coupon_echo = '0đ';
     
        }
        $output = '';

        $output.='
        <style>
            body{
                font-family:DejaVu Sans;
            }
            .table-styling{
                border:1px solid #000;

            }
            .table-styling tbody tr td{
                border:1px solid #000;
                
            }
        </style>
        <h1><center>HÓA ĐƠN</center></h1>
        <p>Người đặt hàng</p>
        <table class="table-styling">
            <thead>
                <tr>
                    <th>Tên khách đặt</th>
                    <th>Số điện thoại</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>';
            
        $output .= '
                <tr>
                    <td>'.$customer->customer_name.'</td>
                    <td>'.$customer->customer_phone.'</td>
                    <td>'.$customer->customer_email.'</td>
                    
                </tr>';
            
        $output .= '
            </tbody>

        </table>
        
        <p>Ship hàng đến</p>
        <table class="table-styling">
            <thead>
                <tr>
                    <th>Tên người nhận</th>
                    <th>Địa chỉ</th>
                    <th>Số điện thoại</th>
                    <th>Email</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>';
            
        $output .= '
                <tr>
                    <td>'.$shipping->shipping_name.'</td>
                    <td>'.$shipping->shipping_address.'</td>
                    <td>'.$shipping->shipping_phone.'</td>
                    <td>'.$shipping->shipping_email.'</td>
                    <td>'.$shipping->shipping_notes.'</td>
                </tr>';
            
        $output .= '
            </tbody>

        </table>

        <p>Đơn hàng</p>
        <table class="table-styling">
            <thead>
                <tr>
                    <th>Tên sản phẩm</th>
                    <th>Mã giảm giá</th>
                    <th>Phí ship</th>
                    <th>Số lượng</th>
                    <th>Giá sản phẩm</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>';
            $total = 0;
            
            foreach($order_details_product as $key => $product){
                $subtotal = $product->product_price*$product->product_sales_quantity;
                $total += $subtotal;
                if($product->product_coupon!='no'){
                    $product_coupon = $product->product_coupon;
                }else{
                    $product_coupon = 'Không có mã giảm gía được áp dụng';
                }
                
        $output .= '
                <tr>
                    <td>'.$product->product_name.'</td>
                    <td>'.$product->product_coupon.'</td>
                    <td>'.number_format($product->product_feeship,0,',','.').'đ'.'</td>
                    <td>'.$product->product_sales_quantity.'</td>
                    <td>'.number_format($product->product_price,0,',','.').'đ'.'</td>
                    <td>'.number_format($subtotal,0,',','.').'đ'.'</td>
                </tr>';
            }
            if($coupon_condition==1){
                $total_after_coupon = ($total*$coupon_number)/100;
                $total_coupon = $total - $total_after_coupon;
            }else{
                $total_coupon = $total - $coupon_number;
            }
        $output .= '
        <tr>
            <td colspan="2">
                <p>Tổng giảm : '.number_format($coupon_number,0,',','.').'đ'.'</p>
                <p>Phí ship : '.number_format($product->product_feeship,0,',','.').'đ'.'</p>
                <p>Thanh toán : '.number_format($total_coupon-$product->product_feeship,0,',','.').'đ'.'</p>
            </td>
        </tr>
        ';
        $output .= '
            </tbody>

        </table>
        
        <p>Ký tên</p>
        <table>
            <thead>
                <tr>
                    <th width="200px">Người lập phiếu</th>
                    <th width="800px">Người nhận</th>
                    
                </tr>
            </thead>
            <tbody>';
            
       
        $output .= '
            </tbody>

        </table>
        
        ';

        
        return $output;
    }
    public function view_order($order_code){
        $order_details = OrderDetails::with('product')->where('order_code',$order_code)->get();
        $order = Order::where('order_code',$order_code)->get();
        foreach($order as $key => $ord){
            $customer_id = $ord->customer_id;
            $shipping_id = $ord->shipping_id;
            $order_status = $ord->order_status;
        }
        $customer = Customer::where('customer_id',$customer_id)->first();
        $shipping = Shipping::where('shipping_id',$shipping_id)->first();
        $order_details_product = OrderDetails::with('product')->where('order_code',$order_code)->get();

        foreach($order_details_product as $key => $order_d){
            $product_coupon = $order_d->product_coupon;
            
        }

        if($product_coupon != 'no'){
            $coupon = Coupon::where('coupon_code',$product_coupon)->first();
            $coupon_condition = $coupon->coupon_condition;
            $coupon_number = $coupon->coupon_number;
        }else{
            $coupon_condition = 2;
            $coupon_number = 0;
        }

        return view('admin.view_order')->with(compact('order_details','customer','shipping','order_details','coupon_condition','coupon_number','order','order_status'));

    }
    public function manage_order(){
        $order = Order::orderby('created_at','DESC')->get();
        return view('admin.manage_order')->with(compact('order'));
    }
    public function update_order_qty(Request $request){
		//update order
		$data = $request->all();
		$order = Order::find($data['order_id']);
		$order->order_status = $data['order_status'];
		$order->save();

        $order_date  = $order->order_date;
        $statistic = Statistic::where('order_date',$order_date)->get();
        if($statistic){
            $statistic_count = $statistic->count();
        }else{
            $statistic_count = 0;
        }

		if($order->order_status==2){
            $total_order = 0;
            $sales = 0;
            $profit = 0;
            $quantity = 0;
			foreach($data['order_product_id'] as $key => $product_id){
				
				$product = Product::find($product_id);
				$product_quantity = $product->product_quantity;
				$product_sold = $product->product_sold;
                $product_price = $product->product_price;
                $product_cost = $product->price_cost;
                $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
				foreach($data['quantity'] as $key2 => $qty){
						if($key==$key2){
								$pro_remain = $product_quantity - $qty;
								$product->product_quantity = $pro_remain;
								$product->product_sold = $product_sold + $qty;
								$product->save();

                                //update doanh thu
                                $quantity+=$qty;
                                $total_order+=1;
                                $sales+=$product_price*$qty;
                                $profit = $sales-($product_cost*$qty);
                                // $profit = ($product_price*$qty)-($product_cost*$qty);
						}
				}
			}
            //update doanh so database
            if($statistic_count>0){
                $statistic_update = Statistic::where('order_date',$order_date)->first();
                $statistic_update->sales = $statistic_update->sales + $sales;
                $statistic_update->profit = $statistic_update->profit + $profit;
                $statistic_update->quantity = $statistic_update->quantity + $quantity;
                // $statistic_update->total_order = $statistic_update->total_order + $total_order;
                $statistic_update->total_order = $statistic_update->total_order + 1;
                $statistic_update->save();
            }else{
                $statistic_new = new Statistic();
                $statistic_new->order_date = $order_date;
                $statistic_new->sales = $sales;
                $statistic_new->profit = $profit;
                $statistic_new->quantity = $quantity;
                $statistic_new->total_order = $total_order;
                $statistic_new->save();
            }
		}
        // elseif($order->order_status!=2 && $order->order_status!=3){
		// 	foreach($data['order_product_id'] as $key => $product_id){
				
		// 		$product = Product::find($product_id);
		// 		$product_quantity = $product->product_quantity;
		// 		$product_sold = $product->product_sold;
		// 		foreach($data['quantity'] as $key2 => $qty){
		// 				if($key==$key2){
		// 						$pro_remain = $product_quantity + $qty;
		// 						$product->product_quantity = $pro_remain;
		// 						$product->product_sold = $product_sold - $qty;
		// 						$product->save();
		// 				}
		// 		}
		// 	}
		// }


	}
    public function order_code(Request $request ,$order_code){
		$order = Order::where('order_code',$order_code)->first();
		$order->delete();
		 Session::put('message','Xóa đơn hàng thành công');
        return redirect()->back();

	}
	public function update_qty(Request $request){
		$data = $request->all();
		$order_details = OrderDetails::where('product_id',$data['order_product_id'])->where('order_code',$data['order_code'])->first();
		$order_details->product_sales_quantity = $data['order_qty'];
		$order_details->save();
	}
}
