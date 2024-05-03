const { Model, DataTypes } = require('sequelize')

const schema = {
	toEmail: DataTypes.STRING
}

class AuditorInvitation extends Model {
	static rawSchema = schema

	static associate(models) {
		this.belongsTo(models.Entity, { as: 'fromEntity', foreignKey: { name: 'fromEntityId', allowNull: false } })
		this.belongsTo(models.DataRequest, { as: 'dataRequest', foreignKey: { name: 'dataRequestId', allowNull: true } })
	}

	static init(options) {
		super.init(schema, { ...options })
	}
}

module.exports = AuditorInvitation
