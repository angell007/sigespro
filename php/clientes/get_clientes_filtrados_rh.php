import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Globales } from '../../../shared/globales/globales';
import { Observable } from 'rxjs';

@Injectable()
export class ClientesService {

  private reducerTotalCartera = (accumulator, currentValue) => accumulator + parseInt(currentValue.Saldo);
  private reducerTotalMora = (accumulator, currentValue) => accumulator + parseInt(currentValue.Mora);

  constructor(private client:HttpClient, private globales:Globales) { }

  getClientesFiltrados(match:string):Observable<any>{  
    let p = {coincidencia:match};
    return this.client.get(this.globales.ruta+'php/clientes/get_clientes_filtrados.php', {params: p});
  }

  public getCarteraCliente(id_cliente) {
    let p = {id_cliente: id_cliente};
    return this.client.get(this.globales.ruta+'php/clientes/get_cartera.php', {params: p});
  }

  public getTotalesCartera(data,tipo) {
    let valor = 0;
    switch (tipo) {
      case 'Total':
          valor = data.reduce(this.reducerTotalCartera, 0);
        break;

      case 'Mora':
          valor = data.reduce(this.reducerTotalMora, 0);
        break;
    }

    return valor;
  }

}
