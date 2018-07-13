export class Channel
{
	static scalar(data, type)
	{
		let buffer = new Uint8Array(data).buffer;

		if(type == 'float')
		{
			return new Float64Array(buffer)[0];
		}
		else if(type == 'Int32')
		{
			return new Int32Array(buffer)[0];
		}
	}

	static namePart(name, part)
	{
		return name.split(':')[part] || null;
	}

	static isWildcard(name)
	{
		return /\*/.exec(name);
	}

	static compareNames(a, b)
	{
		let rangeForm = /^(\d+)\-?(\d+)?$/;

		let result = [];
		let splitA = a.toString().split(':');
		let splitB = b.toString().split(':');
		let nodes  = splitA.length;
		let cmpA;
		let cmpB;

		if(nodes < splitB.length)
		{
			nodes = splitB.length;
		}

		for(let i = 0; i < nodes; i++)
		{
			if(splitA.length > i)
			{
				cmpA = splitA[i];
			}
			else if(splitA[ splitA.length - 1 ] == '*')
			{
				cmpA = splitA[ splitA.length - 1 ];
			}
			else
			{
				return false;
			}

			if(splitB.length > i)
			{
				cmpB = splitB[i];
			}
			else if(splitB[ splitB.length - 1 ] == '*')
			{
				cmpB = splitB[ splitB.length - 1 ];
			}
			else
			{
				return false;
			}

			let returnNode = cmpA !== '*' ? cmpA : cmpB;

			if(cmpA !== cmpB)
			{
				if(cmpA !== '*' && cmpB !== '*')
				{
					let mA = rangeForm.exec(cmpA);
					let mB = rangeForm.exec(cmpB);

					if(mA && mB)
					{
						let a1 = mA[1];
						let a2 = mA[1];
						let b1 = mB[1];
						let b2 = mB[1];

						if(mA[2])
						{
							a2 = mA[2];
						}

						if(mB[2])
						{
							b2 = mB[2];
						}

						if(a1 >= b1 && a2 <= b2)
						{
							returnNode = `${a1}-${a2}`;
						}
						else if(a1 <= b1 && a2 > b2)
						{
							returnNode = `${b1}-${b2}`;
						}
						else if(a2 <= b2 && a2 >= b1)
						{
							returnNode = `${b1}-${a2}`;
						}
						else if(a1 <= b2 && a1 >= b1)
						{
							returnNode = `${a1}-${b2}`;
						}
						if(b2 <= a2 && b2 >= a1)
						{
							returnNode = `${a1}-${b2}`;
						}
						else if(b1 <= a2 && b1 >= a1)
						{
							returnNode = `${b1}-${a2}`;
						}
						else
						{
							return false;
						}
					}
					else
					{
						return false;
					}
				}
			}

			result.push(returnNode);
		}

		return result.join(':');
	}
}
